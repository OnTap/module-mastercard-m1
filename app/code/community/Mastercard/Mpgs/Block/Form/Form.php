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
            '3ds_check_enrolment_url' => Mage::getUrl('mastercard/threedsecure/enrolment', array('_secure' => true)),
            'saved_cards_enabled' => $config->getSavedCardsEnabled(),
            'remove_token_url' => Mage::getUrl('mastercard/token/remove', array('_secure' => true)),
            'saved_cards_require_cvv' => $config->getSavedCardsRequireCvv(),
        ));
    }

    /**
     * @return array
     */
    public function getSavedCards()
    {
        $cards = array();

        if ($this->getSavedCardsEnabled()) {
            /** @var Mage_Customer_Model_Session $config */
            $session = Mage::getSingleton('customer/session');

            /** @var Mage_Customer_Model_Customer $customer */
            $customer = $session->getCustomer();

            /** @var Mastercard_Mpgs_Model_Token $token */
            $token = Mage::getModel('mpgs/token')
                ->getFromCustomer($customer);

            if ($token->isValid()) {
                $cards[] = $token;
            }
        }

        return $cards;
    }

    /**
     * @return bool
     */
    public function getSavedCardsEnabled()
    {
        /** @var Mastercard_Mpgs_Model_Config_Form $config */
        $config = Mage::getSingleton('mpgs/config_form');
        return (bool) $config->getSavedCardsEnabled();
    }

    /**
     * @return bool
     */
    public function getSavedCardsRequireCvv()
    {
        /** @var Mastercard_Mpgs_Model_Config_Form $config */
        $config = Mage::getSingleton('mpgs/config_form');
        return (bool) $config->getSavedCardsRequireCvv();
    }
}
