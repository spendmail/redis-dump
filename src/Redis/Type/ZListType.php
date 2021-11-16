<?php


namespace App\Redis\Type;


class ZListType extends AbstractType implements TypeInterface
{
    const TYPE = '4';

    public static function get($redis, $key)
    {
        return new self($key, $redis->zRange($key, 0, -1));
    }

    public function set($redis)
    {
        foreach ($this->getValue() as $score => $value) {
            $redis->zAdd($this->getKey(), $score, $value);
        }
    }
}
