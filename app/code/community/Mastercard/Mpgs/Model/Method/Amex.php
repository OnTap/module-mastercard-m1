<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Method_Amex extends Mastercard_Mpgs_Model_Method_Abstract implements Mastercard_Mpgs_Model_Method_WalletInterface
{
    const WALLET_CODE = 'AMEX_EXPRESS_CHECKOUT';

    const METHOD_NAME = 'Mastercard_amex';
    protected $_code = self::METHOD_NAME;

    protected $_infoBlockType = 'payment/info';
    protected $_formBlockType = 'mpgs/form_amex';

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
     * Return Mpgs config instance.
     *
     * @return Mastercard_Mpgs_Model_Config_Amex
     */
    public function getConfig()
    {
        return Mage::getSingleton('mpgs/config_amex');
    }

    /**
     * @return string
     */
    public function getButtonRenderer()
    {
        return 'mpgs/checkout_button_amex';
    }

    /**
     * @inheritdoc
     */
    public function openWallet(Mage_Sales_Model_Quote_Payment $payment, Varien_Object $data)
    {
        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton('mpgs/mpgsApi_rest');

        $session = $payment->getAdditionalInformation('session');
        $quote = $payment->getQuote();

        try {
            $wallet = $restAPI->openWallet($session, $quote, self::WALLET_CODE);
            $data->addData(array(
                'wallet' => $wallet['wallet']
            ));
        } catch (Exception $e) {
            $data->addData(array(
                'exception' => $e->getMessage()
            ));
        }
    }

    /**
     * @inheritdoc
     */
    public function updateSessionFromWallet(Mage_Sales_Model_Quote_Payment $payment, Varien_Object $params)
    {
        /** @var Mastercard_Mpgs_Helper_MpgsRest $rest */
        $rest = Mage::helper('mpgs/mpgsRest');

        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton('mpgs/mpgsApi_rest');
        $response = $restAPI->updateSessionFromWallet($params, $payment->getQuote());

        $rest->addCardInfo($payment, $response);
        $rest->addWallet($payment, $response);
        $rest->addSession($payment, $response);
    }

    /**
     * @return string
     */
    public function getConfigPaymentAction()
    {
        // @todo: Take this from provider?
        return Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE;
    }

    /**
     * Authorise the payment.
     * This method is called when auth mode is selected.
     *
     * @param Varien_Object $payment
     * @param string $amount
     * @return $this
     */
    public function authorize( Varien_Object $payment, $amount )
    {
        parent::authorize($payment, $amount);

        /** @var Mastercard_Mpgs_Model_Method_WalletInterface|Mastercard_Mpgs_Model_Method_Abstract $method */
        $method = $payment->getMethodInstance();
        $info = $method->getInfoInstance();

        /** @var Mage_Sales_Model_Order $order */
        $order = $info->getOrder();

        /** @var Mastercard_Mpgs_Helper_MpgsRest $helper */
        $helper = Mage::helper('mpgs/mpgsRest');

        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton('mpgs/mpgsApi_rest');

        $response = $restAPI->authorizeFromSession($order);

//        $mpgs_id = $payment->getAdditionalInformation('mpgs_id');
//        $orderInfo = $restAPI->retrieve_order($mpgs_id);
//        $helper->updatePaymentInfo($payment, $orderInfo);
//
//        $authTxnInfo = $helper->searchTxnByType($orderInfo, 'AUTHORIZATION');
//
//        $helper->updateTransferInfo($payment, $authTxnInfo);
//        $helper->addAuthTxnPayment($payment, $authTxnInfo, false);

        return $this;
    }
}
