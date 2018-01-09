<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */

class Mastercard_Mpgs_Amex_DirectController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    /**
     * Places the order
     */
    public function placeOrderAction()
    {
        $quote = $this->getOnepage()->getQuote();
        $quote->collectTotals();

        $payment = $quote->getPayment();
        $payment->setMethod(Mastercard_Mpgs_Model_Method_Amex::METHOD_NAME);

        /** @var Mastercard_Mpgs_Model_Method_WalletInterface|Mastercard_Mpgs_Model_Method_Abstract $method */
        $method = $payment->getMethodInstance();

        $data = new Varien_Object();
        $data->addData($this->getRequest()->getParams());
        $method->updateSessionFromWallet($payment, $data);

        if ($data->getException()) {
            $this->getResponse()->setHttpResponseCode(503);
            return;
        }

        $method->validate();

        try {
            $this->getOnepage()->saveOrder();
            $quote->save();
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(503);
            $this->_prepareDataJSON(
                array(
                'exception' => $e->getMessage()
                )
            );
            return;
        }

        $next = Mage::getUrl(
            'checkout/onepage/success', array(
            '_secure' => true
            )
        );

        $this->_prepareDataJSON(
            array(
            'success_url' => $next
            )
        );
    }
}
