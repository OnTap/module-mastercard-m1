<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */

class Mastercard_Mpgs_SessionController extends Mastercard_Mpgs_Controller_JsonResponseController
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
}
