<?php


namespace App\Redis\Type;


class ListType extends AbstractType implements TypeInterface
{
    const TYPE = '3';

    public static function get($redis, $key)
    {
        return new self($key, $redis->lRange($key, 0, -1));
    }

    public function set($redis)
    {
        foreach ($this->getValue() as $value) {
            $redis->rPush($this->getKey(), $value);
        }
    }
}
