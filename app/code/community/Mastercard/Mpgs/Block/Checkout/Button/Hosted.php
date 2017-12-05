<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Checkout_Button_Hosted extends Mastercard_Mpgs_Block_Checkout_Button_AbstractButton
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->setTemplate('Mastercard/checkout/button/hosted.phtml');
        parent::_construct();
    }

    /**
     * @return string
     */
    public function getJsAction()
    {
        return sprintf("showMpgsLightbox('%s');", $this->getQuote()->getId());
    }
}
