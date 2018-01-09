<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Config_Amex extends Mastercard_Mpgs_Model_Config
{
    protected $pathApiUsername = 'payment/Mastercard_amex/api_username';
    protected $pathApiPassword = 'payment/Mastercard_amex/api_password';
    protected $pathEndpointUrl = 'payment/Mastercard_amex/end_point_url';
    protected $pathWebhookSecret = 'payment/Mastercard_amex/webhook_secret';
    protected $pathCurrency = 'payment/Mastercard_amex/currency';
    protected $pathTestMode = 'payment/Mastercard_amex/test';
    protected $pathDebug = 'payment/Mastercard_amex/debug';
    protected $pathWebhookUrl = 'payment/Mastercard_amex/webhook_url';
    protected $pathCustomEndPointUrl = 'payment/Mastercard_amex/end_point_custom';

    const XML_COMPONENT_URL = 'payment/Mastercard_amex/component_url';
    const XML_CLIENT_ID = 'payment/Mastercard_amex/client_id';
    const XML_PROVIDER = 'payment/Mastercard_amex/provider';

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

    /**
     * @return string
     */
    public function getRendererBlock()
    {
        $provider = Mage::getStoreConfig(self::XML_PROVIDER);
        return sprintf('mpgs/checkout_button_amex_%s', $provider);
    }

    /**
     * @return string
     */
    public function getSessionComponentUrl()
    {
        $url = $this->getEndPointUrl();
        $url .= 'form/version/' . self::API_VERSION . '/merchant/' . $this->getApiUsername() . '/session.js';
        return $url;
    }
}
