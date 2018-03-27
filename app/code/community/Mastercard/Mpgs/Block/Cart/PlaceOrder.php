<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */

class Mastercard_Mpgs_Block_Cart_PlaceOrder extends Mage_Core_Block_Template
{
    /**
     * @var Mastercard_Mpgs_Block_Cart_Button_AbstractButton[]
     */
    protected $buttons = array();

    /**
     * @return Mage_Core_Block_Abstract
     */
    public function _beforeToHtml()
    {
        foreach ($this->getMethods() as $method) {
            if (!($method instanceof Mastercard_Mpgs_Model_Method_WalletInterface)) {
                continue;
            }
            $blockName = sprintf('%s_%s', $method->getCode(), $method->getConfigData('provider'));
            if ($button = $this->getChild($blockName)) {
                $this->buttons[] = $button;
            }
        }
        return parent::_beforeToHtml();
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/type_onepage')->getQuote();
    }

    /**
     * Check payment method model
     *
     * @param Mage_Payment_Model_Method_Abstract $method
     * @return bool
     */
    protected function _canUseMethod($method)
    {
        return $method->isApplicableToQuote($this->getQuote(), Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
            | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
            | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
        );
    }

    /**
     * Check and prepare payment method model
     *
     * Redeclare this method in child classes for declaring method info instance
     *
     * @param Mage_Payment_Model_Method_Abstract $method
     * @return $this
     */
    protected function _assignMethod($method)
    {
        $method->setInfoInstance($this->getQuote()->getPayment());
        return $this;
    }

    /**
     * Retrieve available payment methods
     *
     * @return Mage_Payment_Model_Method_Abstract[]
     */
    protected function getMethods()
    {
        $methods = $this->getData('methods');
        if ($methods === null) {
            $quote = $this->getQuote();
            $store = $quote ? $quote->getStoreId() : null;
            $methods = array();
            foreach ($this->helper('payment')->getStoreMethods($store, $quote) as $method) {
                if ($this->_canUseMethod($method) && $method->isApplicableToQuote(
                        $quote,
                        Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL
                    )) {
                    $this->_assignMethod($method);
                    $methods[] = $method;
                }
            }
            $this->setData('methods', $methods);
        }
        return $methods;
    }

    /**
     * @return string
     */
    public function renderButtonsHtml()
    {
        $output = '';
        foreach ($this->buttons as $button) {
            $output .= $button->toHtml();
        }
        return $output;
    }
}
