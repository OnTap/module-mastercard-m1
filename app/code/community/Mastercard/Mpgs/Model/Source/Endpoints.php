<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Source_Endpoints
{
    /**
     * Return a list of available endpoints
     *
     * @return array
     * @author Rafael Waldo Delgado Doblas
     */
    public function toOptionArray() 
    {
        foreach (Mage::getSingleton('mpgs/config')->getEndPoints() as $name => $url) {
            $options [] = array (
                    'value' => $url,
                    'label' => $name
            );
        }

        return $options;
    }
}
