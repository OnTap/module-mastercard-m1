<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
abstract class Mastercard_Mpgs_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * @return Mastercard_Mpgs_Model_Config
     */
    abstract public function getConfig();

    /**
     * @return mixed
     */
    abstract public function getButtonRenderer();

    /**
     * Check method for processing with base currency.
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency( $currencyCode ) 
    {
        return $this->getConfig()->getCurrency() === $currencyCode;
    }

    /**
     * @param Varien_Object $payment
     * @return bool
     */
    public function canVoid(Varien_Object $payment)
    {
        if ($payment instanceof Mage_Sales_Model_Order_Payment) {
            return $payment->getAmountPaid() == 0;
        }

        if ($payment instanceof Mage_Sales_Model_Order_Invoice) {
            return $payment->getGrandTotal() == 0;
        }

        return $this->_canVoid;
    }

    /**
     * @return bool
     */
    public function canRefund() 
    {
        $payment = $this->getInfoInstance()->getOrder()->getPayment();
        $disableFlag = $payment->getAdditionalInformation('disableRefund');
        if (empty($disableFlag)) {
            return $this->_canRefund;
        }

        return $disableFlag !== '1';
    }

    /**
     * Validate payment method information object
     *
     * @return Mastercard_Mpgs_Model_Method_Abstract
     * @throws Mage_Core_Exception
     */
    public function validate() 
    {
        /**
         * to validate payment method is allowed for billing country or not
         */
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $baseCurrency = $paymentInfo->getOrder()->getStore()->getBaseCurrencyCode();
        } else {
            $baseCurrency = $paymentInfo->getQuote()->getStore()->getBaseCurrencyCode();
        }

        if (! $this->canUseForCurrency($baseCurrency)) {
            $helper = Mage::helper('mpgs');
            Mage::throwException(Mage::helper('payment')->__($helper->maskDebugMessages('Selected payment type is not allowed for currency.')));
        }

        return $this;
    }

    /**
     * Refund the payment
     *
     * @param Varien_Object $payment
     * @param string $amount
     * @return Mastercard_Mpgs_Model_Method_Abstract
     * @author Rafael Waldo Delgado Doblas
     */
    public function refund( Varien_Object $payment, $amount ) 
    {
        parent::refund($payment, $amount);
        $helper = Mage::helper('mpgs/mpgsRest');

        $refundInfo = $payment->getAdditionalInformation('webhook_info');
        if (empty($refundInfo)) {
            $mpgs_id = $payment->getAdditionalInformation('mpgs_id');
            $currency = $payment->getOrder()->getStore()->getBaseCurrencyCode();
            $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);
            $refundInfo = $restAPI->refund_order($mpgs_id, $amount, $currency);
        }

        $helper->updateTransferInfo($payment, $refundInfo);

        if ($payment->getLastTransId() != $refundInfo ['transaction'] ['id']) {
            $helper->addRefundTxnPayment($payment, $refundInfo);
        }

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param Varien_Object $payment
     * @return Mastercard_Mpgs_Model_Method_Abstract
     * @author Rafael Waldo Delgado Doblas
     */
    public function cancel( Varien_Object $payment ) 
    {
        parent::cancel($payment);
        $helper = Mage::helper('mpgs/mpgsRest');

        $voidInfo = $payment->getAdditionalInformation('webhook_info');
        if (empty($voidInfo)) {
            $mpgs_id = $payment->getAdditionalInformation('mpgs_id');
            $txnid = 1;
            $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);
            $voidInfo = $restAPI->void_order($mpgs_id, $txnid);
        }

        $transactionAuth = $payment->getAuthorizationTransaction();

        $helper->updateTransferInfo($payment, $voidInfo);
        $helper->addVoidTxnPayment($payment, $voidInfo, $transactionAuth->getTxnId());

        return $this;
    }

    /**
     * Void the payment
     *
     * @param Varien_Object $payment
     * @return Mastercard_Mpgs_Model_Method_Abstract
     * @author Rafael Waldo Delgado Doblas
     */
    public function void( Varien_Object $payment ) 
    {
        parent::void($payment);
        $this->cancel($payment);
        return $this;
    }
}
