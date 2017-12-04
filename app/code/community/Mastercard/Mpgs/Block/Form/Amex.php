<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Form_Amex extends Mage_Payment_Block_Form
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Mastercard/form/amex.phtml');
    }
}
