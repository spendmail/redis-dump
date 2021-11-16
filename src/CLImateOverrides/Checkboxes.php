<?php


namespace App\CLImateOverrides;

use League\CLImate\TerminalObject\Dynamic\Checkboxes as VendorCheckboxes;

class Checkboxes extends VendorCheckboxes
{
    /**
     * Added getter for the $this->checkboxes->checkboxes,
     * because the original properties are private
     *
     * @return array
     */
    public function getCheckboxes()
    {
        return $this->checkboxes->getCheckboxes();
    }

    /**
     * Replaces League\CLImate\TerminalObject\Dynamic\Checkbox\CheckboxGroup
     * by App\CLImateOverrides\CheckboxGroup
     *
     * @param array $options
     * @return CheckboxGroup|\League\CLImate\TerminalObject\Dynamic\Checkbox\CheckboxGroup
     */
    protected function buildCheckboxes(array $options)
    {
        return new CheckboxGroup($options);
    }
}
