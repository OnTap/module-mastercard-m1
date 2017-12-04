<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Logger
{
    const LOGFILE = 'mastercard.log';

    /**
     * @var Mastercard_Mpgs_Model_Config
     */
    protected $config;

    /**
     * Mastercard_Mpgs_Model_Logger constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->config = $data['config'];
    }

    /**
     * @param array $data
     */
    public function logDebug($data, $level = null)
    {
        $debug = $this->config->isDebugEnabled();

        if ($debug) {
            Mage::log(var_export($data, 1), $level, self::LOGFILE);
        }
    }
}
