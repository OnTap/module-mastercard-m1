<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

class Mastercard_Mpgs_Model_MpgsApi_Validator_Enrollment extends Mastercard_Mpgs_Model_MpgsApi_Validator
{
    /**
     * @param array $response
     * @return bool
     * @throws Mastercard_Mpgs_Model_MpgsApi_Validator_NotEnrolledException
     * @throws Mastercard_Mpgs_Model_MpgsApi_Validator_ValidationException
     */
    public function validate($response)
    {
        $status = $response['3DSecure']['veResEnrolled'];

        switch ($status) {
            case 'N':
                throw new Mastercard_Mpgs_Model_MpgsApi_Validator_NotEnrolledException('Card not enrolled');

            case 'Y':
                return true;

            default:
                throw new Mastercard_Mpgs_Model_MpgsApi_Validator_ValidationException('3D Secure Validation Failed');
        }
    }
}
