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

}
