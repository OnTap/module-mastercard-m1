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
class Mastercard_Mpgs_Model_Config extends Varien_Object
{
    const API_VERSION = '45';
    const WEB_HOOK_UPDATE_URL = 'mastercard/webhook/update';
    const TRANSACTION_TYPES = 'global/Mastercard/transaction/types';
    const END_POINTS = 'global/Mastercard/endpoints';

    protected $pathApiUsername = null;
    protected $pathApiPassword = null;
    protected $pathEndpointUrl = null;
    protected $pathWebhookSecret = null;
    protected $pathCustomEndPointUrl = null;
    protected $pathTestMode = null;
    protected $pathWebhookUrl = null;
    protected $pathCurrency = null;
    protected $pathDebug = null;
    protected $pathSendLineItems = null;

    /**
     * @return string
     * @throws Exception
     */
    public function getType()
    {
        throw new Exception('Mastercard_Mpgs_Model_Config::getType not implemented. Implement in subclasses.');
    }

    /**
     * Retrieve MPGS Webhook Notifications URL.
     * @return string|null
     * @throws Exception
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
                'type' => $this->getType()
            )
        );
    }

    /**
     * Retrieve an array of transaction types.
     *
     * @return array
     */
    public function getTransactionTypes() 
    {
        $_types = Mage::getConfig()->getNode(self::TRANSACTION_TYPES)->asArray();

        $types = array ();
        foreach ($_types as $data) {
            if (isset($data ['code']) && isset($data ['name'])) {
                $types [$data ['code']] = $data ['name'];
            }
        }

        return $types;
    }

    /**
     * Retrieve an array of end points.
     *
     * @return array
     */
    public function getEndPoints() 
    {
        $_endPoints = Mage::getConfig()->getNode(self::END_POINTS)->asArray();

        $endPoints = array ();
        foreach ($_endPoints as $endPoint) {
            if (isset($endPoint ['name']) && isset($endPoint ['url'])) {
                $endPoints [$endPoint ['name']] = $endPoint ['url'];
            }
        }

        return $endPoints;
    }

    /**
     * Retrieve MPGS API username.
     *
     * @return string
     */
    public function getApiUsername() 
    {
        $username = Mage::getStoreConfig($this->pathApiUsername);
        if (Mage::getStoreConfig($this->pathTestMode) == 1) {
            $username = 'TEST' . $username;
        }

        return $username;
    }

    /**
     * Retrieve MPGS API password.
     *
     * @return string
     */
    public function getApiPasswordDecrypted() 
    {
        $password = Mage::getStoreConfig($this->pathApiPassword);

        return Mage::helper('core')->decrypt($password);
    }

    /**
     * @return string
     */
    public function getEndPointUrl()
    {
        $url = Mage::getStoreConfig($this->pathEndpointUrl);

        if ($url == 'custom') {
            $url = Mage::getStoreConfig($this->pathCustomEndPointUrl);
        }

        $url .= substr($url, - 1) !== '/' ? '/' : '';

        return $url;
    }

    /**
     * Retrieve MPGS NVP API url.
     *
     * @return string
     */
    public function getRestApiUrl() 
    {
        $url = $this->getEndPointUrl();
        $url .= 'api/rest/version/' . static::API_VERSION . '/merchant/';

        return $url;
    }

    /**
     * Retrieve MPGS Webhook Notifications Secret.
     *
     * @return string
     */
    public function getWebhookSecret() 
    {
        $secret = Mage::getStoreConfig($this->pathWebhookSecret);
        return Mage::helper('core')->decrypt($secret);
    }

    /**
     * Retrieve Supported Currency.
     *
     * @return string
     */
    public function getCurrency() 
    {
        return Mage::getStoreConfig($this->pathCurrency);
    }

    /**
     * Retrieve if the Debug mode is enabled.
     *
     * @return string
     */
    public function isDebugEnabled() 
    {
        return Mage::getStoreConfig($this->pathDebug);
    }

    /**
     * @param $msg
     * @return string
     */
    public function maskDebugMessage($msg)
    {
        if ($this->isDebugEnabled()) {
            return $msg;
        }
        return "Internal Error";
    }

    /**
     * @return bool
     */
    public function getSendLineItems()
    {
        return (bool) Mage::getStoreConfig($this->pathSendLineItems);
    }
}
