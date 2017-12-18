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
}
