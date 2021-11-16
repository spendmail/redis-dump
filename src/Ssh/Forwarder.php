<?php


namespace App\Ssh;


class Forwarder
{
    /**
     * Localhost ip address
     * @var string
     */
    protected $localIp = '127.0.0.1';

    /**
     * Local tcp port
     * @var int
     */
    protected $localPort;

    /**
     * Pid of ssh process
     * @var int
     */
    protected $pid;

    /**
     * Remote host for ssh connection
     * @var string
     */
    protected $remoteHost;

    /**
     * Remote tcp-port
     * @var int
     */
    protected $remotePort;

    public function __construct($remoteHost, $remotePort)
    {
        $this->remoteHost = $remoteHost;
        $this->remotePort = (int)$remotePort;
    }

    public function __destruct()
    {
        $this->closePort();
    }

    /**
     * Returns free tcp port
     * @return int
     * @throws \Exception
     */
    protected function getUnusedPort()
    {
        for ($port = 49152; $port < 65535; $port++) {
            if ($fp = @fsockopen('127.0.0.1', $port)) {
                fclose($fp);
            } else {
                return $port;
            }
        }
        throw new \Exception('Can\'t find unused port');
    }

    /**
     * Forwards remote tcp-port to local tcp-port using ssh
     * @throws \Exception
     */
    protected function forwardRemotePort()
    {
        $command = sprintf('ssh -t -t -N -L %s:%s:%s %s 1>/dev/null 2>/dev/null & echo $!', $this->localPort, $this->localIp, $this->remotePort, $this->remoteHost);
        $this->pid = (int)shell_exec($command);
    }

    /**
     * Returns local tcp-port
     * @return int
     * @throws \Exception
     */
    public function getLocalPort()
    {
        if ($this->localPort === null) {
            $this->localPort = $this->getUnusedPort();
        }

        if ($this->pid === null) {
            $this->forwardRemotePort();
        }

        return $this->localPort;
    }

    /**
     * Returns local ip address
     * @return string
     */
    public function getLocalIp()
    {
        return $this->localIp;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Kills process, that opened ssh
     */
    public function closePort()
    {
        shell_exec(sprintf('kill -9 %s', $this->pid));
    }
}
