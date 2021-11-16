<?php


namespace App\Redis\Type;


interface TypeInterface
{
    public static function get($redis, $key);
    public function set($redis);
}