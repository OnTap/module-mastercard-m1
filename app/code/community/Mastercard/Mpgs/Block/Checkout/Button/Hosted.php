<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Checkout_Button_Hosted extends Mastercard_Mpgs_Block_Checkout_Button_AbstractButton
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->setTemplate('Mastercard/checkout/button/hosted.phtml');
        parent::_construct();
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
                'component_url' => $config->getJsComponentUrl(),
                'store_name' => $this->jsQuoteEscape($this->getStoreName(), '"'),
                'cart_id' => $this->getQuote()->getId(),
                'create_session_url' => Mage::getUrl('mastercard/checkout/createSession', array('_secure' => true)),
                'merchant' => $config->getApiUsername(),
                'cancel_url' => Mage::getUrl('mastercard/order/cancel', array('_secure' => true)),
            )
        );
    }
}
