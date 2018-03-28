<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Checkout_Button_Amex_Hpf extends Mastercard_Mpgs_Block_Checkout_Button_AbstractButton
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->setTemplate('Mastercard/amex/hpf_button.phtml');
        parent::_construct();
    }

    /**
     * @return string
     */
    public function getJsConfig()
    {
        $quote = $this->getQuote();
        return json_encode(
            array(
                'debug' => $this->getConfig()->isDebugEnabled(),
                'component_url' => $this->getConfig()->getSessionComponentUrl(),
                'client_id' => $this->getConfig()->getClientId(),
                'env' => $this->getConfig()->getEnv(),
                'grand_total' => $quote->getGrandTotal(),
                'currency' => $quote->getQuoteCurrencyCode(),
                'place_order_url' => Mage::getUrl('mastercard/amex_hpf/placeOrder', array('_secure' => true)),
                'save_payment_url' => Mage::getUrl('mastercard/session/setPaymentInformation', array(
                    '_secure' => true,
                    'method' => Mastercard_Mpgs_Model_Method_Amex::METHOD_NAME
                )),
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
