<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */
class Mastercard_Mpgs_OrderController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    /**
     * Clear reserved order ID for current quote
     * @return Mage_Core_Controller_Varien_Action
     */
    public function cancelAction()
    {
        $this->getOnepage()->getQuote()->setReservedOrderId(null)->save();

        $this->getSession()->addError($this->__('Payment cancelled'));
        return $this->_redirect('checkout/cart/index', array('_secure' => true));
    }
}
