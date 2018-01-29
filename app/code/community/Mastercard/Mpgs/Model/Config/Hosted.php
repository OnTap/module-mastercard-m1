<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Config_Hosted extends Mastercard_Mpgs_Model_Config
{
    protected $pathCurrency = 'payment/Mastercard_hosted/currency';
    protected $pathCustomEndPointUrl = 'payment/Mastercard_hosted/end_point_custom';
    protected $pathTestMode = 'payment/Mastercard_hosted/test';
    protected $pathWebhookUrl = 'payment/Mastercard_hosted/webhook_url';
    protected $pathDebug = 'payment/Mastercard_hosted/debug';
    protected $pathApiUsername = 'payment/Mastercard_hosted/api_username';
    protected $pathApiPassword = 'payment/Mastercard_hosted/api_password';
    protected $pathEndpointUrl = 'payment/Mastercard_hosted/end_point_url';
    protected $pathWebhookSecret = 'payment/Mastercard_hosted/webhook_secret';

    /**
     * Retrieve MPGS Webhook Notifications URL.
     * @return string|null
     */
    public function getWebhookNotificationUrl()
    {
        $webhookSecret = $this->getWebhookSecret();
        if (empty($webhookSecret)) {
            return null;
        }

        $url = Mage::getStoreConfig($this->pathWebhookUrl);
        if (!empty($url)) {
            return $url;
        }

        return Mage::getUrl(
            self::WEB_HOOK_UPDATE_URL, array(
                '_secure' => true,
                'type' => 'hosted'
            )
        );
    }
}
