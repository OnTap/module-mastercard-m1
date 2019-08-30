<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

class Mastercard_Mpgs_TokenController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    /**
     * Remove token from customer account
     * @throws Exception
     */
    public function removeAction()
    {
        /** @var Mage_Customer_Model_Session $session */
        $session = Mage::getSingleton('customer/session');

        if ($session->isLoggedIn()) {
            $customer = Mage::getModel('customer/customer')->load($session->getCustomerId());
            $customer
                ->setData('mpgs_card_token', '')
                ->save();

            $this->_prepareDataJSON(array());
        } else {
            $this->getResponse()
                ->setHeader('HTTP/1.1', '502 Error Deleting Token')
                ->sendResponse();
        }
    }
}
