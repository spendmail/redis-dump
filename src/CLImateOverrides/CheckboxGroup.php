<?php


namespace App\CLImateOverrides;


use League\CLImate\TerminalObject\Dynamic\Checkbox\CheckboxGroup as VendorCheckboxGroup;

class CheckboxGroup extends VendorCheckboxGroup
{
    /**
     * Added getter for the $this->checkboxes->checkboxes,
     * because the original properties are private
     *
     * @return array
     */
    public function getCheckboxes()
    {
        return $this->checkboxes;
    }
}
