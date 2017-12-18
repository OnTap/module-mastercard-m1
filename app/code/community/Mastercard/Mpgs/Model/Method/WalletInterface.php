<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
interface Mastercard_Mpgs_Model_Method_WalletInterface
{
    /**
     * @param Mage_Sales_Model_Quote_Payment $payment
     * @param Varien_Object $data
     * @return void
     */
    public function openWallet(Mage_Sales_Model_Quote_Payment $payment, Varien_Object $data);

    /**
     * @param Mage_Sales_Model_Quote_Payment $payment
     * @param Varien_Object $params
     * @return void
     */
    public function updateSessionFromWallet(Mage_Sales_Model_Quote_Payment $payment, Varien_Object $params);
}
