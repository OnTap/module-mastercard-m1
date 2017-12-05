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

        $method = $payment->getMethodInstance();
        if ($method instanceof Mastercard_Mpgs_Model_Method_WalletInterface) {
            $payment->getMethodInstance()->openWallet($payment, $returnData);
        }

        if ($returnData->getException()) {
            $this->getResponse()->setHttpResponseCode(503);
        }

        $this->getQuote()->save();

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($returnData->toArray()));
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/type_onepage')->getQuote();
    }
}
