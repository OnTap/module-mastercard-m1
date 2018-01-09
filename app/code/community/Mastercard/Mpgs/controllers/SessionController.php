<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_SessionController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    /**
     * Create Session Action
     */
    public function createAction()
    {
        $cartId = $this->getRequest()->getParam('cartId');

        $quote = $this->getQuote();

        if ($cartId != $quote->getId()) {
            Mage::throwException('Cart ID not found');
        }

        $quote->reserveOrderId();
        $quote->save();

        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton(
            'mpgs/mpgsApi_rest', array(
                'config' => Mage::getSingleton('mpgs/config_hosted')
            )
        );

        $resData = $restAPI->create_checkout_session($quote->getReservedOrderId(), $quote);

        $payment = $quote->getPayment();
        $payment->setAdditionalInformation('successIndicator', $resData['successIndicator']);
        $payment->setAdditionalInformation('mpgs_id', $quote->getReservedOrderId());
        $payment->save();

        $dataOut = array();
        $dataOut['merchant'] = $resData['merchant'];
        $dataOut['SessionID'] = $resData['session']['id'];
        $dataOut['SessionVersion'] = $resData['session']['version'];

        $this->_prepareDataJSON($dataOut);
    }

    /**
     * Action
     */
    public function walletAction()
    {
        $payment = $this->getQuote()->getPayment();

        $returnData = new Varien_Object();

        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);
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
