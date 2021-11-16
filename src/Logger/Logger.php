<?php


namespace App\Logger;


class Logger
{

    public function info($message)
    {
        printf('%s%s', $message, PHP_EOL);
    }
}
