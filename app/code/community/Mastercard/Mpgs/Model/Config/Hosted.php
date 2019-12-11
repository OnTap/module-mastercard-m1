<?php
/**
 * Copyright (c) 2016-2019 Mastercard
 *  
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *  
 * http://www.apache.org/licenses/LICENSE-2.0
 *  
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
    protected $pathSendLineItems = 'payment/Mastercard_hosted/send_line_items';

    /**
     * @return string
     */
    public function getType()
    {
        return Mastercard_Mpgs_Model_Method_Hosted::METHOD_CODE;
    }

    /**
     * @return string
     */
    public function getJsComponentUrl()
    {
        $url = $this->getEndPointUrl();
        $url .= 'checkout/version/' . self::API_VERSION . '/checkout.js';

        return $url;
    }
}
