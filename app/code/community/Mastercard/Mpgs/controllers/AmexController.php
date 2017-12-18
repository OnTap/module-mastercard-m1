<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */

class Mastercard_Mpgs_AmexController extends Mage_Core_Controller_Front_Action
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

        $method = $payment->getMethodInstance();
        $method->validate();

        $this->getOnepage()->saveOrder();
        $quote->save();

        $next = Mage::getUrl('checkout/onepage/success', array(
            '_secure' => true
        ));

        $this->_prepareDataJSON(array(
            'success_url' => $next
        ));
    }

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
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
