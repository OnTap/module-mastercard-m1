<?php
/**
 * Copyright (c) 2016-2019 Mastercard
 *  
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *  
 * http://www.apache.org/licenses/LICENSE-2.0
 *  
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
