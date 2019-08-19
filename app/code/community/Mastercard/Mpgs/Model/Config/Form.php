<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

class Mastercard_Mpgs_Model_Config_Form extends Mastercard_Mpgs_Model_Config
{
    protected $pathCurrency = 'payment/Mastercard_form/currency';
    protected $pathCustomEndPointUrl = 'payment/Mastercard_form/end_point_custom';
    protected $pathTestMode = 'payment/Mastercard_form/test';
    protected $pathWebhookUrl = 'payment/Mastercard_form/webhook_url';
    protected $pathDebug = 'payment/Mastercard_form/debug';
    protected $pathApiUsername = 'payment/Mastercard_form/api_username';
    protected $pathApiPassword = 'payment/Mastercard_form/api_password';
    protected $pathEndpointUrl = 'payment/Mastercard_form/end_point_url';
    protected $pathWebhookSecret = 'payment/Mastercard_form/webhook_secret';

    /**
     * @return string
     */
    public function getType()
    {
        return Mastercard_Mpgs_Model_Method_Form::METHOD_CODE;
    }

    /**
     * @return string
     */
    public function getJsComponentUrl()
    {
        $url = $this->getEndPointUrl();
        $url .= sprintf('form/version/%s/merchant/%s/session.js', self::API_VERSION, $this->getApiUsername());

        return $url;
    }
}
