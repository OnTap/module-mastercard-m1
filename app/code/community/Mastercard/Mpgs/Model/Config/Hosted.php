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

}
