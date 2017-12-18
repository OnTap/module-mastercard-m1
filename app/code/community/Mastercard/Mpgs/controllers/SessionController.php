<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_SessionController extends Mage_Core_Controller_Front_Action
{
    /**
     * Action
     */
    public function walletAction()
    {
        $returnData = new Varien_Object();

        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton('mpgs/mpgsApi_rest');
        $session = $restAPI->createSession();

        $payment = $this->getQuote()->getPayment();
        $payment->setAdditionalInformation('session', $session);

        /** @var Mastercard_Mpgs_Model_Method_WalletInterface $method */
        $method = $payment->getMethodInstance();
        if ($method instanceof Mastercard_Mpgs_Model_Method_WalletInterface) {
            $method->openWallet($payment, $returnData);
        }

        if ($returnData->getException()) {
            $this->getResponse()->setHttpResponseCode(503);
        }

        $this->getQuote()->save();

        $this->_prepareDataJSON(
            $returnData->toArray()
        );
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/type_onepage')->getQuote();
    }

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
