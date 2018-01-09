<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Source_Authtypes
{
    /**
     * Return a list of available authentication types
     *
     * @return array
     * @author Alistair Stead
     */
    public function toOptionArray() 
    {
        $options = array();
        foreach (Mage::getSingleton('mpgs/config')->getTransactionTypes() as $code => $name) {
            $options[] = array (
                'value' => $code,
                'label' => $name
            );
        }
        return $options;
    }
}
