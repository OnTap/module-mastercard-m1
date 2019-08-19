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
}
