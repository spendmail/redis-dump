<?php


namespace App\Redis\Type;


class SetType extends AbstractType implements TypeInterface
{
    const TYPE = '2';

    public static function get($redis, $key)
    {
        return new self($key, $redis->sMembers($key));
    }

    public function set($redis)
    {
        foreach ($this->getValue() as $value) {
            $redis->sAdd($this->getKey(), $value);
        }
    }
}
