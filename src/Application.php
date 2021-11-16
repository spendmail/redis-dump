<?php


namespace App;


use App\Exception\AppException;
use App\Redis\Redis;

class Application
{
    const SRC_ARG_KEY = '--from';
    const SRC_REDIS_PASS_ARG_KEY = '--from-redis-pass';
    const SRC_REDIS_PORT_ARG_KEY = '--from-redis-port';
    const DST_ARG_KEY = '--to';
    const DST_REDIS_PASS_ARG_KEY = '--to-redis-pass';
    const DST_REDIS_PORT_ARG_KEY = '--to-redis-port';
    const FLUSH_DST_ARG_KEY = '--flush-destination-server';
    const KEY_DELIMITER_ARG_KEY = '--key-delimiter';

    protected $sourceAddress;
    protected $sourceRedisPassword;
    protected $sourceRedisPort;
    protected $destinationAddress;
    protected $destinationRedisPassword;
    protected $destinationRedisPort;
    protected $flushDestinationServer;
    protected $keyDelimiter;

    public function __construct($options)
    {

        $this->sourceAddress = empty($options[self::SRC_ARG_KEY]) ? null : $options[self::SRC_ARG_KEY];
        $this->sourceRedisPassword = $options[self::SRC_REDIS_PASS_ARG_KEY] ?? null;
        $this->sourceRedisPort = empty($options[self::SRC_REDIS_PORT_ARG_KEY]) ? Redis::DEFAULT_PORT : (int)$options[self::SRC_REDIS_PORT_ARG_KEY];
        $this->destinationAddress = empty($options[self::DST_ARG_KEY]) ? null : $options[self::DST_ARG_KEY];
        $this->destinationRedisPassword = $options[self::DST_REDIS_PASS_ARG_KEY] ?? null;
        $this->destinationRedisPort = empty($options[self::DST_REDIS_PORT_ARG_KEY]) ? Redis::DEFAULT_PORT : (int)$options[self::DST_REDIS_PORT_ARG_KEY];
        $this->flushDestinationServer = isset($options[self::FLUSH_DST_ARG_KEY]) ? (bool)$options[self::FLUSH_DST_ARG_KEY] : false;
        $this->keyDelimiter = empty($options[self::KEY_DELIMITER_ARG_KEY]) ? Redis::DEFAULT_KEY_DELIMITER : $options[self::KEY_DELIMITER_ARG_KEY];
    }

    public function start()
    {
        $sourceRedis = new Redis($this->sourceAddress, $this->sourceRedisPassword, $this->sourceRedisPort, [
            'flushDestinationServer' => $this->flushDestinationServer,
            'keyDelimiter' => $this->keyDelimiter,
        ]);
        $destinationRedis = new Redis($this->destinationAddress, $this->destinationRedisPassword, $this->destinationRedisPort);

        $sourceRedis->dumpTo($destinationRedis);
    }

    public function isValid()
    {
        if ($this->sourceAddress === null) {
            throw new AppException('Source address is required');
        }

        if ($this->destinationAddress === null) {
            throw new AppException('Destination address is required');
        }

        if ($this->sourceAddress === $this->destinationAddress) {
            throw new AppException('Source and destination can not be the same');
        }

        return true;
    }
}
