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
        if (!isset($response['3DSecure']['summaryStatus'])) {
            return $this->createResult(false, array(Mage::helper('core')->__('3D-Secure verification error.')));
        }

        switch ($response['3DSecure']['summaryStatus']) {
            case 'AUTHENTICATION_SUCCESSFUL':
            case 'CARD_DOES_NOT_SUPPORT_3DS':
                $result = $this->createResult(true);
                break;

            default:
            case 'AUTHENTICATION_NOT_AVAILABLE':
            case 'AUTHENTICATION_FAILED':
            case 'AUTHENTICATION_ATTEMPTED':
                $result = $this->createResult(false, array(Mage::helper('core')->__('Transaction declined by 3D-Secure validation.')));
                break;
        }

        return $result;
    }
}
