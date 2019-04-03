<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

class Mastercard_Mpgs_Block_Checkout_Osc_Hosted extends Mage_Core_Block_Template
{
    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/type_onepage')->getQuote();
    }

    /**
     * @return string
     */
    protected function getStoreName()
    {
        $name = Mage::getStoreConfig('general/store_information/name');
        if (!$name) {
            $name = 'Magento Store';
        }
        return (string) $name;
    }

    /**
     * @return string
     */
    public function getJsConfig()
    {
        $config = Mage::getSingleton('mpgs/config_hosted');
        return json_encode(
            array(
                'component_url' => $config->getJsApiUrl(),
                'store_name' => $this->jsQuoteEscape($this->getStoreName(), '"'),
                'cart_id' => $this->getQuote()->getId(),
                'create_session_url' => Mage::getUrl('mastercard/checkout/createSession', array('_secure' => true)),
                'merchant' => $config->getApiUsername(),
                'cancel_url' => Mage::getUrl('mastercard/order/cancel', array('_secure' => true)),
            )
        );
    }
}
