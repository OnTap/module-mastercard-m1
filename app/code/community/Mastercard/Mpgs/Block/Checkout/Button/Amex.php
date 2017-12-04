<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Checkout_Button_Amex extends Mastercard_Mpgs_Block_Checkout_Button_AbstractButton
{
    public function _construct()
    {
        $this->setTemplate('Mastercard/checkout/button/amex.phtml');
        parent::_construct();
    }
}
