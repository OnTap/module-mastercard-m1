<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Checkout_Button_Form extends Mastercard_Mpgs_Block_Checkout_Button_AbstractButton
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->setTemplate('Mastercard/checkout/button/form.phtml');
        parent::_construct();
    }
}
