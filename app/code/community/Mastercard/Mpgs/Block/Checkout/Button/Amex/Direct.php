<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Checkout_Button_Amex_Direct extends Mastercard_Mpgs_Block_Checkout_Button_AbstractButton
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->setTemplate('Mastercard/amex/direct_button.phtml');
        parent::_construct();
    }

    /**
     * @return string
     */
    public function getJsConfig()
    {
        $params = array(
            '_secure' => true,
            'method' => Mastercard_Mpgs_Model_Method_Amex::METHOD_NAME
        );

        return json_encode(
            array(
                'save_payment_url' => Mage::getUrl('mastercard/session/setPaymentInformation', $params),
                'wallet_url' => Mage::getUrl('mastercard/session/openWallet', $params),
                'session_url' => Mage::getUrl('mastercard/session/createSession', $params),
                'place_order_url' => Mage::getUrl('mastercard/amex_direct/placeOrder', array('_secure' => true)),
                'component_url' => $this->getConfig()->getComponentUrl(),
                'client_id' => $this->getConfig()->getClientId(),
                'env' => $this->getConfig()->getEnv(),
            )
        );
    }

    /**
     * Return Mpgs config instance.
     *
     * @return Mage_Core_Model_Abstract|Mastercard_Mpgs_Model_Config_Amex
     */
    public function getConfig()
    {
        return Mage::getSingleton('mpgs/config_amex');
    }
}
