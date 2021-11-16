<?php


namespace App\Redis\Type;


class HashType extends AbstractType implements TypeInterface
{
    const TYPE = '5';

    public static function get($redis, $key)
    {
        return new self($key, $redis->hGetAll($key));
    }

    public function set($redis)
    {
        foreach ($this->getValue() as $hKey => $value) {
            $redis->hSet($this->getKey(), $hKey, $value);
        }
    }
}
