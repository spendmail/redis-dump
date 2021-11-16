<?php


namespace App\Redis\Type;


class StringType extends AbstractType implements TypeInterface
{
    const TYPE = '1';

    public static function get($redis, $key)
    {
        return new self($key, $redis->get($key));
    }

    public function set($redis)
    {
        return $redis->set($this->getKey(), $this->getValue());
    }
}
