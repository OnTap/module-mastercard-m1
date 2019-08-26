<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Form_Form extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Mastercard/form/form.phtml');
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * @return string
     */
    public function getJsConfig()
    {
        /** @var Mastercard_Mpgs_Model_Config_Form $config */
        $config = Mage::getSingleton('mpgs/config_form');
        return json_encode(array(
            'component_url' => $config->getJsComponentUrl(),
            'cart_id' => $this->getQuote()->getId(),
            'create_session_url' => Mage::getUrl('mastercard/checkout/createSession', array('_secure' => true)),
            'merchant' => $config->getApiUsername(),
            '3ds_enabled' => $config->get3dSecureEnabled(),
            '3ds_check_enrolment_url' => Mage::getUrl('mastercard/threedsecure/enrolment', array('_secure' => true))
        ));
    }
}
