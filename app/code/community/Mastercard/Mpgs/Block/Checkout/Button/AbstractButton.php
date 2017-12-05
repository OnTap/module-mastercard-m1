<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Checkout_Button_AbstractButton extends Mage_Core_Block_Template
{
    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $quote;

    /**
     * Mastercard_Mpgs_Block_Checkout_Button_AbstractButton constructor.
     * @param array $args
     */
    public function __construct(array $args = array())
    {
        $this->quote = $args['quote'];
        parent::__construct($args);
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }
}
