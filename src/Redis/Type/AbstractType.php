<?php


namespace App\Redis\Type;


abstract class AbstractType
{
    protected $value;

    protected $key;

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    protected function __construct($key, $value)
    {
        $this->value = $value;
        $this->key = $key;
    }
}