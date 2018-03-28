<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */

class Mastercard_Mpgs_CheckoutController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    /**
     * Create Session Action
     * @throws Mage_Core_Exception
     * @throws Exception
     */
    public function createSessionAction()
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
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/type_onepage')->getQuote();
    }
}
