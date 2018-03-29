<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */

class Mastercard_Mpgs_Block_Review_Shipping extends Mage_Checkout_Block_Onepage_Abstract
{
    /**
     * Quote object setter
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return $this
     */
    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    /**
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getAddress()
    {
        return $this->_quote->getShippingAddress();
    }
}
