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
