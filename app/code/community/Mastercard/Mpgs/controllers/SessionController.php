<?php
/**
 * Copyright (c) 2016-2019 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
     * @throws Zend_Controller_Response_Exception
     */
    public function setShippingInformationAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->norouteAction();
            return;
        }

        $result = array();

        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');

        $method = $this->getRequest()->getPost('shipping_method', '');
        if ($method) {
            $result = $this->getOnepage()->saveShippingMethod($method);
            $this->getOnepage()->getQuote()->collectTotals()->save();
            $session->addSuccess($this->__('Shipping method updated.'));
        }

        $data = $this->getRequest()->getPost('shipping', array());
        if (!empty($data)) {
            $result = $this->getOnepage()->saveShipping($data, null);
            $session->addSuccess($this->__('Shipping address updated.'));
        }

        if (isset($result['error'])) {
            $this->getResponse()->setHttpResponseCode(400);
        }

        $this->_prepareDataJSON($result);
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
