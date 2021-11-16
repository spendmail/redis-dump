<?php


namespace App\CLImateOverrides;


use League\CLImate\TerminalObject\Router\DynamicRouter as VendorDynamicRouter;

class DynamicRouter extends VendorDynamicRouter
{
    /**
     * Replaces League\CLImate\TerminalObject\Dynamic\Checkboxes
     * by App\CLImateOverrides\Checkboxes
     *
     * @param string $class
     * @return string
     */
    public function path($class)
    {
        if ($class === 'checkboxes') {
            return Checkboxes::class;
        }

        return parent::path($class);
    }
}
