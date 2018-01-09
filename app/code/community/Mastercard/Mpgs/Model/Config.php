<?php
/**
 * Mastercard
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@Mastercard.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://testserver.Mastercard.com/software/download.cgi
 * for more information.
 *
 * @author Rafael Waldo Delgado Doblas
 * @version $Id$
 * @copyright Mastercard, 1 Jul, 2016
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @package Mastercard
 **/

/**
 * Class Mastercard_Mpgs_Model_Config
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
        $url .= 'api/rest/version/' . self::API_VERSION . '/merchant/';

        return $url;
    }

    /**
     * Retrieve MPGS JS API url.
     *
     * @return string
     */
    public function getJsApiUrl() 
    {
        $url = $this->getEndPointUrl();
        $url .= 'checkout/version/' . self::API_VERSION . '/checkout.js';

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
     * Retrieve MPGS Webhook Notifications URL.
     *
     * @return string
     */
    public function getWebhookNotificationUrl() 
    {
        $webhookSecret = $this->getWebhookSecret();
        if (empty($webhookSecret)) {
            return;
        }

        $url = Mage::getStoreConfig($this->pathWebhookUrl);
        if (! empty($url)) {
            return $url;
        }

        return Mage::getUrl(
            self::WEB_HOOK_UPDATE_URL, array (
                '_secure' => true
            ) 
        );
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
}
