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
}
