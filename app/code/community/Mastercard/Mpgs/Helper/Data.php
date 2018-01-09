<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Mask the message if debug mode is off.
     *
     * @param string $msg
     * @return string
     */
    public function maskDebugMessages( $msg ) 
    {
        $config = Mage::getSingleton('mpgs/config');
        if ($config->isDebugEnabled()) {
            return $msg;
        }

        return "Internal Error";
    }
}
