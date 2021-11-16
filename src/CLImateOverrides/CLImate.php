<?php


namespace App\CLImateOverrides;


use League\CLImate\CLImate as VendorCLImate;
use League\CLImate\TerminalObject\Router\Router;

class CLImate extends VendorCLImate
{
    /**
     * Replaces League\CLImate\TerminalObject\Router\DynamicRouter
     * by App\CLImateOverrides\DynamicRouter
     *
     * CLImate constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setRouter(new Router(new DynamicRouter));
    }
}
