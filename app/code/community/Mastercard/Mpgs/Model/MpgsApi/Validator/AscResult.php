<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

class Mastercard_Mpgs_Model_MpgsApi_Validator_AscResult extends Mastercard_Mpgs_Model_MpgsApi_Validator
{
    /**
     * @param $response
     * @return bool
     * @throws Mastercard_Mpgs_Model_MpgsApi_Validator_ValidationException
     */
    public function validate($response)
    {
        if (!isset($response['response']['gatewayRecommendation'])) {
            return $this->createResult(false, array(Mage::helper('core')->__('3D-Secure verification error.')));
        }

        switch ($response['response']['gatewayRecommendation']) {
            case 'PROCEED':
                $result = $this->createResult(true);
                break;

            default:
            case 'DO_NOT_PROCEED':
                $result = $this->createResult(false, array(Mage::helper('core')->__('Transaction declined by 3D-Secure validation.')));
                break;
        }

        return $result;
    }
}
