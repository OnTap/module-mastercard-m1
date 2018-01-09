<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Helper_MpgsRest extends Mage_Core_Helper_Abstract
{
    /**
     * @param Mage_Sales_Model_Quote_Payment $payment
     * @param array $data
     */
    public function addWallet(Mage_Sales_Model_Quote_Payment $payment, $data)
    {
        $payment->setAdditionalInformation('wallet', $data['wallet']);
    }

    /**
     * @param Mage_Sales_Model_Quote_Payment $payment
     * @param array $data
     */
    public function addSession(Mage_Sales_Model_Quote_Payment $payment, $data)
    {
        $payment->setAdditionalInformation('session', $data['session']);
    }

    /**
     * Verifies if a field exist in the data array otherwise return null
     *
     * @param string $data
     * @param string $field
     * @return string|null
     */
    public function safeValue( $data, $field ) 
    {
        return isset($data [$field]) ? $data [$field] : null;
    }

    /**
     * Transfrom a multidimension array in a single dimension with dot separed keys
     *
     * @param array $array
     * @param array $parsed
     * @param string $path
     */
    public function multiarray_to_plainarray( array $array, &$parsed, $path = null ) 
    {
        foreach ($array as $k => $v) {
            if (! is_array($v)) {
                $fulpath = substr($path, 1) . '.' . $k;
                $parsed [$fulpath] = $v;
            } else {
                $this->multiarray_to_plainarray($v, $parsed, $path . '.' . $k);
            }
        }
    }

    /**
     * @param $payment
     * @param $data
     */
    public function addResult( $payment, $data ) 
    {
        if (isset($data ['result'])) {
            $payment->setAdditionalInformation('txn_result', $data ['result']);
        }
    }

    /**
     * @param $payment
     * @param $data
     */
    public function addRisk( $payment, $data ) 
    {
        if (isset($data ['risk'])) {
            $plain = array ();
            $this->multiarray_to_plainarray($data ['risk'], $plain);
            $payment->setAdditionalInformation('risk', $plain);
        }
    }

    /**
     * @param $payment
     * @param $data
     */
    public function addCardInfo( $payment, $data ) 
    {
        if (isset($data ['sourceOfFunds']) && isset($data ['sourceOfFunds'] ['provided'] ['card'])) {
            $cardDetails = $data ['sourceOfFunds'] ['provided'] ['card'];

            $payment->setAdditionalInformation('card_scheme', $cardDetails ['scheme']);
            $payment->setAdditionalInformation('card_number', 'XXXX-' . substr($cardDetails ['number'], - 4));
            $payment->setAdditionalInformation('card_expiry_date', sprintf('%s/%s', $cardDetails ['expiry'] ['month'], $cardDetails ['expiry'] ['year']));
            if (isset($cardDetails ['fundingMethod'])) {
                $payment->setAdditionalInformation('fundingMethod', $this->safeValue($cardDetails, 'fundingMethod'));
            }

            if (isset($cardDetails ['issuer'])) {
                $payment->setAdditionalInformation('issuer', $this->safeValue($cardDetails, 'issuer'));
            }

            if (isset($cardDetails ['nameOnCard'])) {
                $payment->setAdditionalInformation('nameOnCard', $this->safeValue($cardDetails, 'nameOnCard'));
            }
        }

        if (isset($data ['response'] ['cardSecurityCode'])) {
            $payment->setAdditionalInformation('cvv_validation', $data ['response'] ['cardSecurityCode'] ['gatewayCode']);
        }
    }

    /**
     * Update payment information with the information provided by MPGS
     *
     * @param $payment
     * @param $data
     */
    public function updatePaymentInfo( $payment, $data ) 
    {
        $this->addResult($payment, $data);
        $this->addRisk($payment, $data);
        $this->addCardInfo($payment, $data);
        $payment->save();
    }

    /**
     * Update transfer information with the information provided by MPGS about transaction.
     *
     * @param $payment
     * @param $txndata
     */
    public function updateTransferInfo( $payment, $txndata ) 
    {
        if (isset($txndata ['response'] ['gatewayCode'])) {
            $payment->setAdditionalInformation('response_gatewayCode', $txndata ['response'] ['gatewayCode']);
        }

        if (isset($txndata ['order'])) {
            $plain = array ();
            $this->multiarray_to_plainarray($txndata ['order'], $plain);
            $payment->setAdditionalInformation('order', $plain);
        }

        $payment->save();
    }

    /**
     * Build a create checkout session Transaction block.
     *
     * @return array
     */
    public function buildTransactionData() 
    {
        $transaction ['source'] = 'INTERNET';
        return $transaction;
    }

    /**
     * Build a create checkout session Interaction block.
     *
     * @return array
     */
    public function buildInteractionData() 
    {

        $interaction ['displayControl'] ['customerEmail'] = 'HIDE';
        $interaction ['displayControl'] ['billingAddress'] = 'HIDE';
        $interaction ['displayControl'] ['orderSummary'] = 'HIDE';
        $interaction ['displayControl'] ['paymentTerms'] = 'HIDE';
        $interaction ['displayControl'] ['shipping'] = 'HIDE';

        return $interaction;
    }

    /**
     * Build a create checkout session Customer block.
     *
     * @param Mage_Sales_Model_Quote|Mage_Sales_Model_Order $quote
     * @return array
     */
    public function buildCustomerData( $quote ) 
    {
        $billingAddress = $quote->getBillingAddress();
        $customer ['email'] = $billingAddress->getEmail();
        $customer ['firstName'] = $billingAddress->getFirstname();
        $customer ['lastName'] = $billingAddress->getLastname();
        $customer ['phone'] = $billingAddress->getTelephone();

        return $customer;
    }

    /**
     * Build a create checkout session Billing block.
     *
     * @param Mage_Sales_Model_Quote|Mage_Sales_Model_Order $quote
     * @return array
     */
    public function buildBillingData( $quote ) 
    {
        $billingAddress = $quote->getBillingAddress();
        $billingCountry_2 = $billingAddress->getCountryId();
        $billingCountry_3 = Mage::getModel('directory/country')->loadByCode($billingCountry_2)->getIso3_code();
        $billingStreet = $billingAddress->getStreet();

        $billing ['address'] ['city'] = $billingAddress->getCity();
        $billing ['address'] ['company'] = $billingAddress->getCompany() != '' ? $billingAddress->getCompany() : null;
        $billing ['address'] ['country'] = $billingCountry_3;
        $billing ['address'] ['postcodeZip'] = $billingAddress->getPostcode();
        $billing ['address'] ['stateProvince'] = $billingAddress->getRegionCode();
        $billing ['address'] ['street'] = $billingStreet [0];
        $billing ['address'] ['street2'] = count($billingStreet) > 1 ? $billingStreet [1] : null;

        return $billing;
    }

    /**
     * Build a create checkout session Shipping block.
     *
     * @param Mage_Sales_Model_Quote|Mage_Sales_Model_Order $quote
     * @return array
     */
    public function buildShippingData( $quote ) 
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingCountry_2 = $shippingAddress->getCountryId();
        $shippingCountry_3 = Mage::getModel('directory/country')->loadByCode($shippingCountry_2)->getIso3_code();
        $shippingStreet = $shippingAddress->getStreet();

        $shipping ['address'] ['city'] = $shippingAddress->getCity();
        $shipping ['address'] ['company'] = $shippingAddress->getCompany() != '' ? $shippingAddress->getCompany() : null;
        $shipping ['address'] ['country'] = $shippingCountry_3;
        $shipping ['address'] ['postcodeZip'] = $shippingAddress->getPostcode();
        $shipping ['address'] ['stateProvince'] = $shippingAddress->getRegionCode();
        $shipping ['address'] ['street'] = $shippingStreet [0];
        $shipping ['address'] ['street2'] = count($shippingStreet) > 1 ? $shippingStreet [1] : null;
        $shipping ['contact'] ['email'] = $shippingAddress->getEmail();
        $shipping ['contact'] ['firstName'] = $shippingAddress->getFirstname();
        $shipping ['contact'] ['lastName'] = $shippingAddress->getLastname();
        $shipping ['contact'] ['phone'] = $shippingAddress->getTelephone();

        return $shipping;
    }

    /**
     * Build order array
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function buildOrderDataFromOrder($order)
    {
        $pricesIncludeTax = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX);
        if ($pricesIncludeTax) {
            $order['taxAmount'] = number_format($order->getShippingAddress()->getData('tax_amount'), 2);
        }

        $data = array();

        $data['amount'] = sprintf('%.2F', $order->getGrandTotal());
        $data['currency'] = $order->getBaseCurrencyCode();
        $data['shippingAndHandlingAmount'] = number_format($order->getShippingAmount(), 2);
        $data['description'] = 'Magento Order';
        $data['reference'] = $order->getIncrementId();
        $data['item'] = array();

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            $iteminfo['name'] = $item->getName();
            $iteminfo['description'] = $item->getDescription();
            $iteminfo['sku'] = $item->getSku();
            // XXX: Item is always sent with qty = 1 because
            // XXX: otherwise we would run into rounding errors when row total is calculated
            $iteminfo['unitPrice'] = sprintf('%.2F', $item->getRowTotal() - $item->getDiscountAmount());
            $iteminfo['quantity'] = 1;
            $data['item'][] = $iteminfo;
        }

        return $data;
    }

    /**
     * Build a create checkout session Order block.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param Mastercard_Mpgs_Model_Config $config
     * @return array
     */
    public function buildOrderDataFromQuote($quote, $config)
    {
        $order = array();

        $pricesIncludeTax = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX);
        if ($pricesIncludeTax) {
            $order['taxAmount'] = number_format($quote->getShippingAddress()->getData('tax_amount'), 2);
        }

        $order['amount'] = sprintf('%.2F', $quote->getGrandTotal());
        $order['currency'] = $quote->getStore()->getBaseCurrencyCode();
        $order['shippingAndHandlingAmount'] = number_format($quote->getShippingAddress()->getShippingAmount(), 2);
        $order['description'] = 'Magento Order';
        $order['notificationUrl'] = $config->getWebhookNotificationUrl();
        $order['reference'] = $quote->getReservedOrderId();
        $i = 0;

        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($quote->getAllItems() as $item) {
            $iteminfo['name'] = $item->getName();
            $iteminfo['description'] = $item->getDescription();
            $iteminfo['sku'] = $item->getSku();
            // XXX: Item is always sent with qty = 1 because
            // XXX: otherwise we would run into rounding errors when row total is calculated
            $iteminfo['unitPrice'] = sprintf('%.2F', $item->getRowTotal() - $item->getDiscountAmount());
            $iteminfo['quantity'] = 1;
            $order['item'][$i] = $iteminfo;
            ++$i;
        }

        return $order;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param string $type
     * @return array
     */
    public function buildWalletData($quote, $type)
    {
        return array(
            'amount' => sprintf('%.2F', $quote->getGrandTotal()),
            'currency' => $quote->getStore()->getBaseCurrencyCode(),
            'walletProvider' => $type
        );
    }

    /**
     * @param Varien_Object $params
     * @return array
     */
    public function aecDataBulder($params)
    {
        return array(
            'amexExpressCheckout' => array(
                'authCode' => $params->getData('auth_code'),
                'transactionId' => $params->getData('transaction_id'),
                'walletId' => $params->getData('wallet_id'),
                'selectedCardType' => $params->getData('card_type')
            )
        );
    }

    /**
     * @return array
     */
    public function buildSourceOfFunds()
    {
        return array(
            'type' => 'CARD'
        );
    }

    /**
     * @param array $session
     * @return array
     */
    public function buildSessionVersionData($session)
    {
        return array(
            'version' => $session['session']['version']
        );
    }

    /**
     * @param array $session
     * @return array
     */
    public function buildSessionData($session)
    {
        return array(
            'id' => $session['id']
        );
    }

    /**
     * Return a label for the supplied key
     *
     * Used to transpose object data keys to correct UI labels and also add
     * translation functionality
     *
     * @return string $key
     */
    public function getLabel($key)
    {
        $labels = array (
                'response_gatewayCode' => 'Gateway Code',
                'txn_result' => 'Transaction Result',
                'auth_code' => 'Auth Code',
                'card_scheme' => 'Card Scheme',
                'card_number' => 'Card Number',
                'card_expiry_date' => 'Card Expiry Date',
                'fundingMethod' => 'Funding Method',
                'issuer' => 'Card Issuer',
                'nameOnCard' => 'Name on the Card',
                'cvv_validation' => 'Cvv Validation',
                'response.gatewayCode' => 'Risk Gateway Code',
                'response.review.decision' => 'Risk Review',
                'response.totalScore' => 'Risk total Score',
                ".status" => 'Order Status',
                ".totalAuthorizedAmount" => 'Total Authorized Amount',
                ".totalCapturedAmount" => 'Total Captured Amount',
                ".totalRefundedAmount" => 'Total Refunded Amount'
        );

        return (!empty($labels [$key])) ? $this->__($labels [$key]) : $key;
    }

    /**
     * @param $orderInfo
     * @param $type
     * @return null
     */
    public function searchTxnByType($orderInfo, $type)
    {
        foreach ($orderInfo ['transaction'] as $txnInfo) {
            if ($txnInfo ['transaction'] ['type'] === $type) {
                return $txnInfo;
                break;
            }
        }

        return null;
    }

    /**
     * @param $payment
     * @param $captureInfo
     * @return bool
     */
    public function isAllPaid($payment, $captureInfo)
    {
        $paid = $payment->getAmountPaid() + $captureInfo ['transaction'] ['amount'];
        return $paid >= $payment->getAmountAuthorized();
    }

    /**
     * @param $payment
     * @param $txnInfo
     * @param $close
     * @return mixed
     */
    public function addAuthTxnPayment($payment, $txnInfo, $close)
    {
        return $this->addTxnPayment($payment, $txnInfo, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, $close);
    }

    /**
     * @param $payment
     * @param $txnInfo
     * @param $parentTxnId
     * @param $closeParent
     * @return mixed
     */
    public function addCaptureTxnPayment($payment, $txnInfo, $parentTxnId, $closeParent)
    {
        return $this->addTxnPayment($payment, $txnInfo, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, true, $parentTxnId, $closeParent);
    }

    /**
     * @param $payment
     * @param $txnInfo
     * @return mixed
     */
    public function addRefundTxnPayment($payment, $txnInfo)
    {
        return $this->addTxnPayment($payment, $txnInfo, Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND, true);
    }

    /**
     * @param $payment
     * @param $txnInfo
     * @param $parentTxnId
     * @return mixed
     */
    public function addVoidTxnPayment($payment, $txnInfo, $parentTxnId)
    {
        return $this->addTxnPayment($payment, $txnInfo, Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID, true, $parentTxnId, true);
    }

    /**
     * @param $payment
     * @param $txnInfo
     * @param $type
     * @param $close
     * @param null $parentTxnId
     * @param null $closeParent
     * @return mixed
     */
    protected function addTxnPayment($payment, $txnInfo, $type, $close, $parentTxnId = null, $closeParent = null)
    {
        $payment->setSkipTransactionCreation(false);

        $helper = Mage::helper('mpgs/mpgsRest');

        $payment->setTransactionId($txnInfo ['transaction'] ['id']);
        $payment->setIsTransactionClosed($close);

        $plain = array ();
        $helper->multiarray_to_plainarray($txnInfo, $plain);
        $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $plain);

        if (! empty($parentTxnId)) {
            $payment->setParentTransactionId($parentTxnId);
        }

        if (! empty($closeParent)) {
            $payment->setShouldCloseParentTransaction($closeParent);
        }

        $txn = $payment->addTransaction($type);
        $payment->setSkipTransactionCreation(true);

        return $txn;
    }
}
