<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Method_Hosted extends Mastercard_Mpgs_Model_Method_Abstract
{
    const METHOD_NAME = 'Mastercard_hosted';
    const METHOD_CODE = 'hosted';

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
     * @return Mastercard_Mpgs_Model_Config_Hosted|Mage_Core_Model_Abstract
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
     * @throws Mage_Core_Exception
     */
    protected function verifyResultCode($payment)
    {
        $successIndicator = $payment->getAdditionalInformation("successIndicator");
        if ($this->_resultCode != $successIndicator) {
            Mage::throwException($this->getConfig()->maskDebugMessage("Error successIndicator does't match with resultCode."));
        }
    }

    /**
     * Capture the payment.
     * This method is called when auth and capture mode is selected.
     *
     * @param Varien_Object $payment
     * @param string $amount
     * @return Mastercard_Mpgs_Model_Method_Hosted
     * @throws Exception
     * @throws Mage_Core_Exception
     * @author Rafel Waldo Delgado Doblas
     */
    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);

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

        $orderInfo = $restAPI->retrieve_order($order->getIncrementId());
        $helper->updateTransferInfo($payment, $orderInfo);

        if (empty($txnAuth)) {
            $this->verifyResultCode($payment);

            if (!isset($orderInfo['transaction']['id']) && is_array($orderInfo['transaction'])) {
                foreach ($orderInfo['transaction'] as $key => $txn) {
                    $txn['transaction']['id'] = sprintf('%s-%s', $order->getIncrementId(), $key);
                    switch ($txn['transaction']['type']) {
                        default:
                            throw new Exception('Transaction type not recognised.');
                            break;

                        case 'AUTHORIZATION':
                            $txnAuth = $helper->addTxnPayment($payment, $txn, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, true);
                            break;

                        case 'CAPTURE':
                            if (!$txnAuth) {
                                throw new Exception('Capturing without authorisation.');
                            }
                            $helper->addTxnPayment($payment, $txn, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, true, $txnAuth->getTxnId());
                            break;
                    }
                }
            } else {
                $helper->addPayTnxPayment($payment, $orderInfo);
            }
        } else {
            $captureInfo = $restAPI->capture_order(
                $order->getIncrementId(),
                $amount,
                $order->getOrderCurrencyCode()
            );
            $helper->updateTransferInfo($payment, $captureInfo);
            $helper->addCaptureTxnPayment($payment, $captureInfo, $txnAuth->getTxnId(), $helper->isAllPaid($payment, $captureInfo));
        }

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
     * @throws Mage_Core_Exception
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
