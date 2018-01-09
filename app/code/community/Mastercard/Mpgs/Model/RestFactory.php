<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_RestFactory
{
    protected $methodConfigMapper = array(
        'Mastercard_amex' => 'mpgs/config_amex',
        'Mastercard_hosted' => 'mpgs/config_hosted',
    );

    /**
     * Returns a configured REST client instance
     * based on $payment object
     *
     * @param Varien_Object $payment
     * @return Mastercard_Mpgs_Model_MpgsApi_Rest
     */
    public function get(Varien_Object $payment)
    {
        return Mage::getSingleton(
            'mpgs/mpgsApi_rest', array(
            'config' => Mage::getSingleton($this->methodConfigMapper[$payment->getMethod()])
            )
        );
    }
}