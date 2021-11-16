<?php


namespace App\Redis;


use App\CLImateOverrides\CLImate;
use App\Exception\AppException;
use App\Exception\IncorrectPasswordException;
use App\Logger\Logger;
use App\Redis\Type\HashType;
use App\Redis\Type\ListType;
use App\Redis\Type\SetType;
use App\Redis\Type\StringType;
use App\Redis\Type\ZListType;
use App\Ssh\Forwarder;
use RedisException;

class Redis
{
    const DEFAULT_PORT = 6379;

    const TIMEOUT = 300;

    const HOST_TYPE = 1;

    const FILE_TYPE = 2;

    const DEFAULT_KEY_DELIMITER = ':';

    protected $address;

    protected $remoteAddress;

    protected $password;

    protected $port;

    protected $remotePort;

    protected $redis;

    protected $forwarder;

    protected $type;

    protected $logger;

    protected $flushDestinationServer = false;

    protected $keyDelimiter;

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getRedis()
    {
        return $this->redis;
    }

    public function __construct($address, $password, $port, $options = [])
    {
        $this->logger = new Logger();

        $this->address = $address;
        $this->password = $password;
        $this->port = $port;

        if ($this->isSsh($this->address)) {
            $this->type = self::HOST_TYPE;
            $this->remotePort = $this->port;
            $this->remoteAddress = $this->address;
            $this->forwarder = new Forwarder($this->remoteAddress, $this->remotePort);
            $this->port = $this->forwarder->getLocalPort();
            $this->address = '127.0.0.1';

            if ($this->type === self::HOST_TYPE && $this->remoteAddress && $this->remotePort) {
                $this->logger->info(sprintf("\nRemote port %s\nfrom %s\nhas been forwarded to %s:%s\n(pid = %s)\n",
                    $this->remotePort,
                    $this->remoteAddress,
                    $this->address,
                    $this->port,
                    $this->forwarder->getPid()
                ));
            }

        } elseif ($this->isHost($this->address)) {
            list($this->address, $this->port) = $this->getHostPortFromAddress($this->address);
            $this->type = self::HOST_TYPE;
        } else {
            $this->type = self::FILE_TYPE;
        }

        $this->flushDestinationServer = isset($options['flushDestinationServer']) ? (bool)$options['flushDestinationServer'] : false;
        $this->keyDelimiter = isset($options['keyDelimiter']) ? $options['keyDelimiter'] : self::DEFAULT_KEY_DELIMITER;
    }

    public function dumpTo(Redis $destinationRedis)
    {
        /**
         * File to file dump
         */
        if (
            $this->type === self::FILE_TYPE &&
            $destinationRedis->getType() === self::FILE_TYPE
        ) {
            throw new AppException(sprintf('Error. Probable you are trying to dump from file to file!'));
        }

        /**
         * Host to host dump
         */
        if (
            $this->type === self::HOST_TYPE &&
            $destinationRedis->getType() === self::HOST_TYPE
        ) {
            $this->dumpHostToHost($destinationRedis);
        }

        /**
         * Host to file dump
         */
        if (
            $this->type === self::HOST_TYPE &&
            $destinationRedis->getType() === self::FILE_TYPE
        ) {
            $this->dumpToFile($destinationRedis);
        }

        /**
         * File to host dump
         */
        if (
            $this->type === self::FILE_TYPE &&
            $destinationRedis->getType() === self::HOST_TYPE
        ) {
            $this->dumpFileToHost($destinationRedis);
        }
    }

    public function getAllValues()
    {
        $keys = $this->redis->keys('*');

        $keyPrefixesMap = $this->getKeyPrefixesMap($keys);

        $climate = new CLImate();
        $input = $climate->checkboxes('Deselect useless Redis keys:', array_keys($keyPrefixesMap));
        foreach ($input->getCheckboxes() as $key => $option) {
            $option->setChecked(!$option->isChecked());
        }
        $selectedPrefixes = $input->prompt();

        $selectedKeys = $this->getSelectedKeys($keyPrefixesMap, $selectedPrefixes);

        return $this->getValuesByKeys($selectedKeys);
    }

    protected function dumpToFile(Redis $destinationRedis)
    {
        $this->connect();

        $values = $this->getAllValues();

        $this->saveToFile($destinationRedis->getAddress(), $values);

        $this->redis->close();

        $this->logger->info(sprintf("\n%s keys has been dumped.\n", count($values)));
    }

    protected function dumpFileToHost(Redis $destinationRedis)
    {
        $values = $this->readFromFile($this->address);

        $destinationRedis->connect();

        if ($this->flushDestinationServer === true) {
            $destinationRedis->getRedis()->flushAll();
        }

        foreach ($values as $key => $typeObject) {
            $typeObject->set($destinationRedis->getRedis());
        }

        $destinationRedis->getRedis()->close();
    }

    public function dumpHostToHost(Redis $destinationRedis)
    {
        $this->connect();
        $values = $this->getAllValues();

        $destinationRedis->connect();

        if ($this->flushDestinationServer === true) {
            $destinationRedis->getRedis()->flushAll();
        }

        foreach ($values as $key => $typeObject) {
            $typeObject->set($destinationRedis->getRedis());
        }
    }

    protected function readFromFile($fileName)
    {
        if (!is_readable($fileName)) {
            throw new AppException(sprintf('Can not read from "%s"', $fileName));
        }

        return unserialize(file_get_contents($fileName));
    }

    protected function saveToFile($fileName, $values)
    {
        $dirname = dirname($fileName);
        if (!is_writable($dirname)) {
            throw new AppException(sprintf('Can not write to "%s"', $dirname));
        }

        file_put_contents($fileName, serialize($values));
    }

    protected function getKeyPrefixesMap($keys)
    {
        $keyPrefixesMap = [];
        foreach ($keys as $key) {
            $parts = explode($this->keyDelimiter, $key);
            $shortKey = $parts[0];

            if (empty($keyPrefixesMap[$shortKey])) $keyPrefixesMap[$shortKey] = [];

            $keyPrefixesMap[$shortKey][] = $key;
        }

        return $keyPrefixesMap;
    }

    protected function getSelectedKeys($keyPrefixesMap, $selectedPrefixes)
    {
        $selectedKeys = [];

        foreach ($selectedPrefixes as $selectedPrefix) {
            if (empty($keyPrefixesMap[$selectedPrefix])) continue;
            $selectedKeys = array_merge($selectedKeys, $keyPrefixesMap[$selectedPrefix]);
        }

        return $selectedKeys;
    }

    public function connect()
    {
        //Disable warnings during connecting attempts
        error_reporting(E_ALL ^ E_WARNING);
        for ($i = 0; $i < self::TIMEOUT; $i++) {

            try {
                $this->logger->info(sprintf('Connecting to %s:%s...', $this->address, $this->port));
                $this->redis = new \Redis();
                $this->redis->connect(
                    $this->address,
                    $this->port,
                    0
                );

                if(!empty($this->password)){
                    if (!$authResult = $this->redis->auth($this->password)) {
                        throw new IncorrectPasswordException(sprintf('The password "%s" is incorrect', $this->password));
                    }
                }

                break;
            } catch (RedisException $e) {
                $this->logger->info(sprintf('%s, retry...', $e->getMessage()));
            }

            sleep(1);
        }
        error_reporting(E_ALL);

        if ($i === self::TIMEOUT) {
            throw new AppException('Redis Server is unavailable');
        }

        $this->logger->info(sprintf('Connecting established'));
    }

    public function isSsh($address)
    {
        return strpos($address, '@') !== false;
    }

    public function isHost($address)
    {

        if (preg_match('@^[^:]+:[\d]+$@ui', $address, $matches)) {
            return true;
        }

        return false;
    }

    public function getHostPortFromAddress($address)
    {
        if (preg_match('@^([^:]+):([\d]+)$@ui', $address, $matches)) {
            return [
                $matches[1],
                (int)$matches[2],
            ];
        }

        throw new AppException(sprintf('Can not determine host and port from server address'));
    }

    protected function getValuesByKeys($keys)
    {
        $data = [];
        foreach ($keys as $selectedKey) {
            $type = $this->redis->type($selectedKey);

            $data[$selectedKey] = $this->getValueByKey($type, $selectedKey);
        }

        return $data;
    }

    protected function getValueByKey($type, $key)
    {
        switch ($type) {
            case StringType::TYPE:
                return StringType::get($this->redis, $key);
                break;

            case SetType::TYPE:
                return SetType::get($this->redis, $key);
                break;

            case HashType::TYPE:
                return HashType::get($this->redis, $key);
                break;

            case ListType::TYPE:
                return ListType::get($this->redis, $key);
                break;

            case ZListType::TYPE:
                return ZListType::get($this->redis, $key);
                break;

            default:
                throw new AppException(sprintf('Redis type "%s" is not supported yet', $type));
        }
    }

//    public function signalHandler($signo)
//    {
//        $this->redis->close();
//        $this->forwarder->closePort();
//        die();
//    }
//
//    public function addSignalsHandler()
//    {
//        declare(ticks=1);
//        if (!function_exists('pcntl_signal')) {
//            return;
//        }
//
//        pcntl_signal(SIGINT, [$this, 'signalHandler']);
//    }
}
