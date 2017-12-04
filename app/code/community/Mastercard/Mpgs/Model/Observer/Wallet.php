<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Observer_Wallet
{
    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function openWallet(Varien_Event_Observer $observer)
    {
        $data = $observer->getReturnData();

        $payment = $observer->getPayment();
        $method = $payment->getMethodInstance();

        if ($method instanceof Mastercard_Mpgs_Model_Method_WalletInterface) {
            $payment->getMethodInstance()->openWallet($payment, $data);
        }

        return $this;
    }
}
