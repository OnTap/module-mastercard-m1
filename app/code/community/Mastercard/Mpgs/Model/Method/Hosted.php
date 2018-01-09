<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Method_Hosted extends Mastercard_Mpgs_Model_Method_Abstract
{
    const METHOD_NAME = 'Mastercard_hosted';
    protected $_code = self::METHOD_NAME;
    protected $_infoBlockType = 'payment/info';

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
    private $_resultCode = '';

    /**
     * @return string
     */
    public function getButtonRenderer()
    {
        return 'mpgs/checkout_button_hosted';
    }

    /**
     * Return Mpgs config instance.
     *
     * @return Mastercard_Mpgs_Model_Config_Hosted
     */
    public function getConfig()
    {
        return Mage::getSingleton('mpgs/config_hosted');
    }

    /**
     * Sets the result code from the MPGS payment response.
     *
     * @param string $resultCode
     *
     */
    public function setResultCode($resultCode)
    {
        $this->_resultCode = $resultCode;
    }

    /**
     * @param $payment
     */
    protected function verifyResultCode($payment)
    {
        $successIndicator = $payment->getAdditionalInformation("successIndicator");
        if ($this->_resultCode != $successIndicator) {
            $helper = Mage::helper('mpgs');
            Mage::throwException($helper->maskDebugMessages("Error successIndicator doesnt match with resultCode."));
        }
    }

    /**
     * Capture the payment.
     * This method is called when auth and capture mode is selected.
     *
     * @param Varien_Object $payment
     * @param string $amount
     * @return Mastercard_Mpgs_Model_Method_Hosted
     * @author Rafel Waldo Delgado Doblas
     */
    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);
        $helper = Mage::helper('mpgs/mpgsRest');

        $txnAuth = $payment->getAuthorizationTransaction();
        $captureInfo = $payment->getAdditionalInformation('webhook_info');
        if (empty($captureInfo)) {
            /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
            $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);

            $mpgs_id = $payment->getAdditionalInformation('mpgs_id');
            $orderInfo = $restAPI->retrieve_order($mpgs_id);
            $helper->updatePaymentInfo($payment, $orderInfo);

            $currency = $payment->getOrder()->getStore()->getBaseCurrencyCode();
            $captureInfo = $restAPI->capture_order($mpgs_id, $amount, $currency);

            if (empty($txnAuth)) {
                // Creates an auth txn on magento side
                $this->verifyResultCode($payment);
                $authTxnInfo = $helper->searchTxnByType($orderInfo, 'AUTHORIZATION');
                $txnAuth = $helper->addAuthTxnPayment($payment, $authTxnInfo, $helper->isAllPaid($payment, $captureInfo));
            }
        }

        // Creates an capture txn on magento side
        $helper->updateTransferInfo($payment, $captureInfo);
        $helper->addCaptureTxnPayment($payment, $captureInfo, $txnAuth->getTxnId(), $helper->isAllPaid($payment, $captureInfo));

        return $this;
    }

    /**
     * Authorise the payment.
     * This method is called when auth mode is selected.
     *
     * @param Varien_Object $payment
     * @param string $amount
     * @return Mastercard_Mpgs_Model_Method_Hosted
     * @author Rafel Waldo Delgado Doblas
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $this->verifyResultCode($payment);

        parent::authorize($payment, $amount);

        $helper = Mage::helper('mpgs/mpgsRest');
        $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);

        $mpgs_id = $payment->getAdditionalInformation('mpgs_id');
        $orderInfo = $restAPI->retrieve_order($mpgs_id);
        $helper->updatePaymentInfo($payment, $orderInfo);

        $authTxnInfo = $helper->searchTxnByType($orderInfo, 'AUTHORIZATION');

        $helper->updateTransferInfo($payment, $authTxnInfo);
        $helper->addAuthTxnPayment($payment, $authTxnInfo, false);

        return $this;
    }
}
