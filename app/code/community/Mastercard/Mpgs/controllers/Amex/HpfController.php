<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */

class Mastercard_Mpgs_Amex_HpfController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    const SESSION_ID = 'id';
    const SESSION_VERSION = 'version';

    /**
     * @return Mage_Core_Controller_Varien_Action
     */
    public function placeOrderAction()
    {
        $quote = $this->getOnepage()->getQuote();
        $quote->collectTotals();

        $payment = $quote->getPayment();
        $payment->setMethod(Mastercard_Mpgs_Model_Method_Amex::METHOD_NAME);

        $payment->setAdditionalInformation(
            'session', array(
            'id' => $this->getRequest()->getParam(self::SESSION_ID)
            )
        );

        try {
            $payment->save();

            /** @var Mastercard_Mpgs_Model_Method_WalletInterface|Mastercard_Mpgs_Model_Method_Abstract $method */
            $method = $payment->getMethodInstance();
            $method->validate();

            $this->getOnepage()->saveOrder();
            $quote->save();
        } catch (Exception $e) {
            $this->getSession()->addError($e->getMessage());
            return $this->_redirect(
                'checkout/cart/index', array(
                '_secure' => true
                )
            );
        }

        return $this->_redirect(
            'checkout/onepage/success', array(
            '_secure' => true
            )
        );
    }
}
