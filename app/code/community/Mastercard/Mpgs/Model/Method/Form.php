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

class Mastercard_Mpgs_Model_Method_Form extends Mastercard_Mpgs_Model_Method_Abstract
{
    const METHOD_NAME = 'Mastercard_form';
    const METHOD_CODE = 'form';

    protected $_code = self::METHOD_NAME;
    protected $_infoBlockType = 'payment/info';
    protected $_formBlockType = 'mpgs/form_form';

    /**
     * Payment Method features.
     *
     * @var bool
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = false;

    /**
     * @return Mastercard_Mpgs_Model_Config_Form|Mage_Core_Model_Abstract
     */
    public function getConfig()
    {
        return Mage::getSingleton('mpgs/config_form');
    }

    /**
     * @return string
     */
    public function getButtonRenderer()
    {
        return 'mpgs/checkout_button_form';
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
     * @param Mage_Sales_Model_Order_Payment|Varien_Object $payment
     * @return $this;
     * @throws Mage_Core_Exception
     */
    protected function setAdditionalData(Varien_Object $payment)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $order->getQuote();

        $payment->setAdditionalInformation('mpgs_id', $payment->getOrder()->getIncrementId());

        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);

        if ($quote) {
            $quotePayment = $quote->getPayment();

            $sessionId = $quotePayment->getData('mpgs_session_id');
            if ($sessionId) {
                $sessionInfo = $restAPI->get_session($sessionId);
                $payment->setAdditionalInformation('session', $sessionInfo['session']);
            }

            $payment->setAdditionalInformation('mpgs_save_card', $quotePayment->getData('mpgs_save_card'));
            $payment->setAdditionalInformation('mpgs_token_hash', $quotePayment->getData('mpgs_token_hash'));
        }

        $payment->save();

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment|Varien_Object $payment
     * @param float $amount
     * @return $this
     * @throws Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);

        $this->setAdditionalData($payment);

        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);

        /** @var Mastercard_Mpgs_Helper_MpgsRest $helper */
        $helper = Mage::helper('mpgs/mpgsRest');

        $txnAuth = $payment->getAuthorizationTransaction();

        // Webhook has updated this already
        $captureInfo = $payment->getAdditionalInformation('webhook_info');
        if (!empty($captureInfo)) {
            $helper->updateTransferInfo($payment, $captureInfo);
            $helper->addCaptureTxnPayment($payment, $captureInfo, $txnAuth->getTxnId(), $helper->isAllPaid($payment, $captureInfo));
            return $this;
        }

        /** @var Mastercard_Mpgs_Model_Method_Abstract $method */
        $method = $payment->getMethodInstance();
        $info = $method->getInfoInstance();

        /** @var Mage_Sales_Model_Order $order */
        $order = $info->getOrder();

        try {
            if (empty($txnAuth)) {
                $this->processAclResult($restAPI, $payment);
                $this->processCreateToken($restAPI, $payment);

                $orderInfo = $restAPI->payFromSession($order);
                $helper->updatePaymentInfo($payment, $orderInfo);
                $helper->addPayTnxPayment($payment, $orderInfo);
            } else {
                $orderInfo = $restAPI->capture_order(
                    $order->getIncrementId(),
                    $amount,
                    $order->getOrderCurrencyCode()
                );
                $helper->addCaptureTxnPayment($payment, $orderInfo, $txnAuth->getTxnId(), $helper->isAllPaid($payment, $captureInfo));
            }
        } catch (Mastercard_Mpgs_Model_MpgsApi_Validator_BlockedException $e) {
            $session = $this->getOnepage()->getCheckout();
            $session->clear();
            throw new Mage_Payment_Model_Info_Exception($e->getMessage(), 1, $e);
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $order = $payment->getOrder();
            $quote = $order->getQuote();
            $quote->setReservedOrderId(null)->reserveOrderId();
            throw $e;
        } catch (Exception $e) {
            throw new Mage_Payment_Model_Info_Exception($e->getMessage());
        }

        $helper->updateTransferInfo($payment, $orderInfo);

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment|Varien_Object $payment
     * @param float $amount
     * @return $this
     * @throws Mage_Core_Exception
     * @throws Exception
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        parent::authorize($payment, $amount);

        $this->setAdditionalData($payment);

        /** @var Mastercard_Mpgs_Helper_MpgsRest $helper */
        $helper = Mage::helper('mpgs/mpgsRest');

        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);

        $this->processAclResult($restAPI, $payment);
        $this->processCreateToken($restAPI, $payment);

        try {
            $orderInfo = $restAPI->authorizeFromSession($payment->getOrder());
        } catch (Mastercard_Mpgs_Model_MpgsApi_Validator_BlockedException $e) {
            $session = $this->getOnepage()->getCheckout();
            $session->clear();
            throw new Mage_Payment_Model_Info_Exception($e->getMessage(), 1, $e);
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $order = $payment->getOrder();
            $quote = $order->getQuote();
            $quote->setReservedOrderId(null)->reserveOrderId();
            throw $e;
        } catch (Exception $e) {
            throw new Mage_Payment_Model_Info_Exception($e->getMessage());
        }

        $helper->updatePaymentInfo($payment, $orderInfo);
        $helper->updateTransferInfo($payment, $orderInfo);
        $helper->addAuthTxnPayment($payment, $orderInfo, false);

        return $this;
    }

    /**
     * @param Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI
     * @param Mage_Sales_Model_Order_Payment|Varien_Object $payment
     * @throws Exception
     */
    protected function processAclResult($restAPI, $payment)
    {
        if ($payment->getAdditionalInformation('mpgs_token_hash')) {
            $payment->setAdditionalInformation('3DSecureId', false);
            return;
        }

        if ($this->getConfig()->get3dSecureEnabled() && !$payment->getAdditionalInformation('3DSecureNotEnrolled')) {
            $paRes = $payment->getAdditionalInformation('PaRes');
            $threeDSecureId = $payment->getAdditionalInformation('3DSecureId');
            $restAPI->process_asc_result($threeDSecureId, $paRes);
        } else {
            $payment->setAdditionalInformation('3DSecureId', false);
        }
    }

    /**
     * @param Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI
     * @param Mage_Sales_Model_Order_Payment|Varien_Object $payment
     * @throws Exception
     */
    protected function processCreateToken($restAPI, $payment)
    {
        $saveCard = $payment->getAdditionalInformation('mpgs_save_card');

        if ($saveCard && !$payment->getOrder()->getCustomerIsGuest()) {
            $session = $payment->getAdditionalInformation('session');

            try {
                /** @var Mage_Customer_Model_Customer $customer */
                $customer = Mage::getModel('customer/customer')->load(
                    $payment->getOrder()->getCustomerId()
                );

                $response = $restAPI->create_token($session['id']);

                /** @var Mastercard_Mpgs_Model_Token $token */
                $token = Mage::getModel('mpgs/token');
                $token->createTokenFromResponse($response);

                $customer
                    ->setData('mpgs_card_token', $token->asJson())
                    ->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }
}
