<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Checkout_Button_Amex extends Mastercard_Mpgs_Block_Checkout_Button_AbstractButton
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->setTemplate('Mastercard/checkout/button/amex.phtml');
        parent::_construct();
    }

    /**
     * @return string
     */
    public function getJsConfig()
    {
        return json_encode(array(
            'component_url' => $this->getConfig()->getComponentUrl(),
            'session_url' => Mage::getUrl('mastercard/session/wallet', array('_secure' => true)),
            'client_id' => $this->getConfig()->getClientId(),
            'env' => $this->getConfig()->getEnv(),
            'place_order_url' => Mage::getUrl('mastercard/amex/placeOrder', array('_secure' => true)),
        ));
    }

    /**
     * Return Mpgs config instance.
     *
     * @return Mastercard_Mpgs_Model_Config_Amex
     */
    public function getConfig()
    {
        return Mage::getSingleton('mpgs/config_amex');
    }
}
