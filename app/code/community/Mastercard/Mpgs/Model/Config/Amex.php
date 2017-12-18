<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Config_Amex extends Mastercard_Mpgs_Model_Config
{
    protected $pathCurrency = 'payment/Mastercard_amex/currency';
    protected $pathTestMode = 'payment/Mastercard_amex/test';
    protected $pathDebug = 'payment/Mastercard_amex/debug';
    protected $pathWebhookUrl = '';
    protected $pathCustomEndPointUrl = '';

    const XML_COMPONENT_URL = 'payment/Mastercard_amex/component_url';
    const XML_CLIENT_ID = 'payment/Mastercard_amex/client_id';

    /**
     * @return mixed
     */
    public function getComponentUrl()
    {
        return Mage::getStoreConfig(self::XML_COMPONENT_URL);
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return Mage::getStoreConfig(self::XML_CLIENT_ID);
    }

    /**
     * @return string
     */
    public function getEnv()
    {
        return Mage::getStoreConfig($this->pathTestMode) == 1 ? 'qa' : 'production';
    }
}
