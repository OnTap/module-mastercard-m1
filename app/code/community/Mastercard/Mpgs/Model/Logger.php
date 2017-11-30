<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Logger
{
    const LOGFILE = 'mastercard.log';

    /**
     * @param array $data
     */
    public function logDebug($data, $level = null)
    {
        $config = Mage::getSingleton('mpgs/config');
        $debug = $config->isDebugEnabled();

        if ($debug) {
            Mage::log(var_export($data, 1), $level, self::LOGFILE);
        }
    }
}
