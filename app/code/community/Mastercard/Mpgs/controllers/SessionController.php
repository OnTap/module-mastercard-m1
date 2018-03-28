<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */

class Mastercard_Mpgs_SessionController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    /**
     * @throws Zend_Controller_Response_Exception
     * @throws Exception
     */
    public function createSessionAction()
    {
        $method = $this->getRequest()->getParam('method');
        try {
            if (!$method) {
                throw new Exception('Payment method not selected.');
            }

            $payment = new Varien_Object(array(
                'method' => $method
            ));

            /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
            $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);
            $data = $restAPI->createSession();
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(400);
            $data = array(
                'exception' => $e->getMessage()
            );
        }

        $this->_prepareDataJSON($data);
    }

    /**
     * @throws Mage_Core_Exception
     */
    public function openWalletAction()
    {
        $session = array(
            'session' => array(
                'id' => $this->getRequest()->getParam('id'),
                'updateStatus' => $this->getRequest()->getParam('updateStatus'),
                'version' => $this->getRequest()->getParam('version')
            )
        );

        $payment = $this->getQuote()->getPayment();
        $payment->setAdditionalInformation('session', $session);
        $payment->setMethod(Mastercard_Mpgs_Model_Method_Amex::METHOD_NAME);

        $returnData = new Varien_Object();

        /** @var Mastercard_Mpgs_Model_Method_WalletInterface $method */
        $method = $payment->getMethodInstance();
        if ($method instanceof Mastercard_Mpgs_Model_Method_WalletInterface) {
            $method->openWallet($payment, $returnData);
        }

        $this->_prepareDataJSON(
            $returnData->toArray()
        );
    }

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function setPaymentInformationAction()
    {
        $data = array();

        try {
            $session = array(
                'id' => $this->getRequest()->getParam('id'),
                'updateStatus' => $this->getRequest()->getParam('updateStatus'),
                'version' => $this->getRequest()->getParam('version')
            );

            $payment = $this->getQuote()->getPayment();
            $payment->setAdditionalInformation('session', $session);
            $payment->setMethod(Mastercard_Mpgs_Model_Method_Amex::METHOD_NAME);

            $this->getQuote()->save();

        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(400);
            $data = array(
                'exception' => $e->getMessage()
            );
        }

        $this->_prepareDataJSON($data);
    }

    /**
     * @return $this
     * @throws Mage_Core_Exception
     * @throws Zend_Controller_Response_Exception
     */
    public function updateSessionFromWalletAction()
    {
        $quote = $this->getOnepage()->getQuote();
        //$quote->collectTotals();

        $payment = $quote->getPayment();

        /** @var Mastercard_Mpgs_Model_Method_WalletInterface|Mastercard_Mpgs_Model_Method_Abstract $method */
        $method = $payment->getMethodInstance();

        $data = new Varien_Object();
        $data->addData($this->getRequest()->getParams());
        $method->updateSessionFromWallet($payment, $data);

        if ($data->getException()) {
            $this->getResponse()->setHttpResponseCode(503);
            return $this;
        }

        $method->validate();
        $quote->save();

        $next = Mage::getUrl(
            'mastercard/review/index', array(
                '_secure' => true
            )
        );

        $this->_prepareDataJSON(
            array(
                'success_url' => $next
            )
        );

        return $this;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/type_onepage')->getQuote();
    }
}
