<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */

abstract class Mastercard_Mpgs_Controller_JsonResponseController extends Mage_Core_Controller_Front_Action
{
    /**
     * Prepare JSON formatted data for response to client
     *
     * @param $response
     * @return Zend_Controller_Response_Abstract
     */
    protected function _prepareDataJSON($response)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage|Mage_Core_Model_Abstract
     */
    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    /**
     * Get checkout session model instance
     *
     * @return Mage_Checkout_Model_Session|Mage_Core_Model_Abstract
     */
    public function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
