<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Source_Backends
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'direct',
                'label' => __('MasterCard Direct Payment')
            ),
            array(
                'value' => 'hpf',
                'label' => __('MasterCard Hosted Session')
            ),
        );
    }
}
