<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_MpgsApi_Rest extends Varien_Object
{
    const MPGS_GET = 0;
    const MPGS_POST = 1;
    const MPGS_PUT = 2;

    /**
     * @var Mastercard_Mpgs_Model_Config
     */
    protected $config;

    /**
     * Mastercard_Mpgs_Model_MpgsApi_Rest constructor.
     * @param $params
     * @throws Exception
     */
    public function __construct($params)
    {
        if (!isset($params['config'])) {
            throw new Exception('Payment Config not passed to REST client');
        }

        $this->config = $params['config'];
        parent::__construct();
    }

    /**
     * @param $err
     */
    protected function _critical($err)
    {
        // @todo: implement
    }

    /**
     * This methods sends a REST HTTP message to the MPGS endpoint
     *
     * @param integer $type
     * @param string $method
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function sender( $type, $method, $data = null )
    {
        $username = $this->config->getApiUsername();
        $password = $this->config->getApiPasswordDecrypted();

        $url = $this->config->getRestApiUrl() . $username . '/' . $method;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, "merchant." . $username . ":" . $password);

        $payload = '';
        if ($data) {
            $payload = json_encode($data);
            switch ($type) {
                case self::MPGS_POST :
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    break;
                case self::MPGS_PUT :
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    break;
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        $resData = json_decode($response, true);

        /** @var Mastercard_Mpgs_Model_Logger $logger */
        $logger = Mage::getSingleton('mpgs/logger', array('config' => $this->config));
        $logger->logDebug(
            array(
            'url' => $url,
            'type' => $type,
            'payload' => $payload,
            'response' => $response
            )
        );

        try {
            /** @var Mastercard_Mpgs_Model_MpgsApi_Validator $validator */
            $validator = Mage::getModel('mpgs/mpgsApi_validator');
            $validator->validate($resData);
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }

        return $resData;
    }

    /**
     * This method creates a checkout session on MPGS.
     *
     * @param string $mpgs_id
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     *
     */
    public function create_checkout_session( $mpgs_id, $quote ) 
    {
        /** @var Mastercard_Mpgs_Helper_MpgsRest $rest */
        $rest = Mage::helper('mpgs/mpgsRest');
        $data ['apiOperation'] = 'CREATE_CHECKOUT_SESSION';
        $data ['transaction'] = $rest->buildTransactionData();
        $data ['interaction'] = $rest->buildInteractionData();
        $data ['customer'] = $rest->buildCustomerData($quote);
        $data ['billing'] = $rest->buildBillingData($quote);
        $data ['shipping'] = $rest->buildShippingData($quote);
        $data ['order'] = $rest->buildOrderDataFromQuote($quote, $this->config);
        $data ['order'] ['id'] = $mpgs_id;

        $resData = $this->sender(self::MPGS_POST, 'session', $data);

        if ($resData ['session'] ['updateStatus'] !== 'SUCCESS') {
            $this->_critical(Mage_Api2_Model_Resource::RESOURCE_INTERNAL_ERROR);
        }

        return $resData;
    }

    /**
     * @param array $session
     * @param Mage_Sales_Model_Quote $quote
     * @param string $type
     * @return array
     */
    public function openWallet($session, $quote, $type)
    {
        /** @var Mastercard_Mpgs_Helper_MpgsRest $rest */
        $rest = Mage::helper('mpgs/mpgsRest');

        $data = array();
        $data['order'] = $rest->buildWalletData($quote, $type);
        $data['session'] = $rest->buildSessionVersionData($session);

        return $this->sender(self::MPGS_POST, 'session/' . $session['session']['id'], $data);
    }

    /**
     * @return array
     */
    public function createSession()
    {
        $resData = $this->sender(
            self::MPGS_POST, 'session', array(
            'correlationId' => null
            )
        );
        return $resData;
    }

    /**
     * @param Varien_Object $params
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public function updateSessionFromWallet(Varien_Object $params, Mage_Sales_Model_Quote $quote)
    {
        /** @var Mastercard_Mpgs_Helper_MpgsRest $rest */
        $rest = Mage::helper('mpgs/mpgsRest');

        $data = array();
        $data['apiOperation'] = 'UPDATE_SESSION_FROM_WALLET';
        $data['order'] = $rest->buildWalletData($quote, Mastercard_Mpgs_Model_Method_Amex::WALLET_CODE);
        $data['wallet'] = $rest->aecDataBulder($params);

        $session = $quote->getPayment()->getAdditionalInformation('session');

        return $this->sender(self::MPGS_POST, 'session/' . $session['session']['id'], $data);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function authorizeFromSession(Mage_Sales_Model_Order $order)
    {
        $orderId = $order->getIncrementId();

        /** @var Mastercard_Mpgs_Helper_MpgsRest $rest */
        $rest = Mage::helper('mpgs/mpgsRest');

        $data = array(
            'apiOperation' => 'AUTHORIZE'
        );

        $data['customer'] = $rest->buildCustomerData($order);
        $data['billing'] = $rest->buildBillingData($order);
        $data['shipping'] = $rest->buildShippingData($order);
        $data['order'] = $rest->buildOrderDataFromOrder($order);
        $data['session'] = $rest->buildSessionData($order->getPayment()->getAdditionalInformation('session'));
        $data['sourceOfFunds'] = $rest->buildSourceOfFunds();

        $txnId = uniqid(sprintf('%s-', $orderId));
        return $this->sender(self::MPGS_PUT, 'order/' . $orderId . '/transaction/' . $txnId, $data);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function payFromSession(Mage_Sales_Model_Order $order)
    {
        $orderId = $order->getIncrementId();

        /** @var Mastercard_Mpgs_Helper_MpgsRest $rest */
        $rest = Mage::helper('mpgs/mpgsRest');

        $data = array(
            'apiOperation' => 'PAY'
        );

        $data['customer'] = $rest->buildCustomerData($order);
        $data['billing'] = $rest->buildBillingData($order);
        $data['shipping'] = $rest->buildShippingData($order);
        $data['order'] = $rest->buildOrderDataFromOrder($order);
        $data['session'] = $rest->buildSessionData($order->getPayment()->getAdditionalInformation('session'));
        $data['sourceOfFunds'] = $rest->buildSourceOfFunds();

        $txnId = uniqid(sprintf('%s-', $orderId));
        return $this->sender(self::MPGS_PUT, 'order/' . $orderId . '/transaction/' . $txnId, $data);
    }

    /**
     * This method retrieve information of an order on MPGS.
     *
     * @param string $mpgs_id
     * @return array
     */
    public function retrieve_order( $mpgs_id ) 
    {
        $data ['apiOperation'] = 'RETRIEVE_ORDER';
        $resData = $this->sender(self::MPGS_GET, 'order/' . $mpgs_id, $data);
        return $resData;
    }

    /**
     * This method retrieve information of a transaction on MPGS.
     *
     * @param string $mpgs_id
     * @return array
     */
    public function retrieve_transaction( $mpgs_id, $txn_id ) 
    {
        $data ['apiOperation'] = 'RETRIEVE_TRANSACTION';
        $resData = $this->sender(self::MPGS_GET, 'order/' . $mpgs_id . '/transaction/' . $txn_id, $data);
        return $resData;
    }

    /**
     * This method captures an order previosly authorized.
     *
     * @param string $mpgs_id
     * @param float $amount
     * @param string $currency
     * @return array
     */
    public function capture_order( $mpgs_id, $amount, $currency ) 
    {
        $data ['apiOperation'] = 'CAPTURE';
        $data ['transaction'] ['amount'] = sprintf('%.2F', $amount);
        $data ['transaction'] ['currency'] = $currency;
        $txnid = uniqid(sprintf('%s-', ( string ) $mpgs_id));

        $resData = $this->sender(self::MPGS_PUT, 'order/' . $mpgs_id . '/transaction/' . $txnid, $data);

        return $resData;
    }

    /**
     * This method refunds an order previosly captured.
     *
     * @param string $mpgs_id
     * @param float $amount
     * @param string $currency
     * @return array
     */
    public function refund_order( $mpgs_id, $amount, $currency ) 
    {
        $data ['apiOperation'] = 'REFUND';
        $data ['transaction'] ['amount'] = sprintf('%.2F', $amount);
        $data ['transaction'] ['currency'] = $currency;
        $txnid = uniqid(sprintf('%s-', ( string ) $mpgs_id));

        $resData = $this->sender(self::MPGS_PUT, 'order/' . $mpgs_id . '/transaction/' . $txnid, $data);

        return $resData;
    }

    /**
     * This method voids an order authorized status.
     *
     * @param string $mpgs_id
     * @param string $txnid
     * @return array
     */
    public function void_order( $mpgs_id, $txnid ) 
    {
        $data ['apiOperation'] = 'VOID';
        $data ['transaction'] ['targetTransactionId'] = $txnid;

        $_txnid = uniqid(sprintf('%s-', ( string ) $mpgs_id));

        $resData = $this->sender(self::MPGS_PUT, 'order/' . $mpgs_id . '/transaction/' . $_txnid, $data);

        return $resData;
    }
}
