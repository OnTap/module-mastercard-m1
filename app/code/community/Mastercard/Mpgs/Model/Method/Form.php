<?php
/**
 * Copyright (c) On Tap Networks Limited.
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

        if (empty($txnAuth)) {
            $orderInfo = $restAPI->payFromSession($order);
            $helper->addPayTnxPayment($payment, $orderInfo);
        } else {
            $orderInfo = $restAPI->capture_order(
                $order->getIncrementId(),
                $amount,
                $order->getOrderCurrencyCode()
            );
            $helper->addCaptureTxnPayment($payment, $orderInfo, $txnAuth->getTxnId(), $helper->isAllPaid($payment, $captureInfo));
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

        $order = $payment->getOrder();
        $payment = $order->getPayment();

        /** @var Mastercard_Mpgs_Helper_MpgsRest $helper */
        $helper = Mage::helper('mpgs/mpgsRest');

        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);

        $orderInfo = $restAPI->authorizeFromSession($order);

        $helper->updatePaymentInfo($payment, $orderInfo);
        $helper->updateTransferInfo($payment, $orderInfo);
        $helper->addAuthTxnPayment($payment, $orderInfo, false);

        return $this;
    }
}
