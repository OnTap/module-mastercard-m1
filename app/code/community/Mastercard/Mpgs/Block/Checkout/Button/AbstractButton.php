<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Checkout_Button_AbstractButton extends Mage_Core_Block_Template
{
    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/type_onepage')->getQuote();
    }
}
