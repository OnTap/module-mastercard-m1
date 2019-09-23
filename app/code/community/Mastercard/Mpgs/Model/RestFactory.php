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
class Mastercard_Mpgs_Model_RestFactory
{
    protected $methodConfigMapper = array(
        'Mastercard_hosted' => 'mpgs/config_hosted',
        'Mastercard_form' => 'mpgs/config_form',
    );

    /**
     * Returns a configured REST client instance
     * based on $payment object
     *
     * @param Varien_Object $payment
     * @return Mastercard_Mpgs_Model_MpgsApi_Rest|Mage_Core_Model_Abstract
     */
    public function get(Varien_Object $payment)
    {
        return Mage::getSingleton(
            'mpgs/mpgsApi_rest', array(
                'config' => Mage::getSingleton($this->methodConfigMapper[$payment->getMethod()])
            )
        );
    }
}
