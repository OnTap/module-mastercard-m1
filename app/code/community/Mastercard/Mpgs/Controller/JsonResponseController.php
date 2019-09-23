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

abstract class Mastercard_Mpgs_Controller_JsonResponseController extends Mage_Core_Controller_Front_Action
{
    /**
     * Prepare JSON formatted data for response to client
     *
     * @param $response
     * @return Zend_Controller_Response_Abstract
     */
    protected function _prepareDataJSON($response)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage|Mage_Core_Model_Abstract
     */
    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    /**
     * Get checkout session model instance
     *
     * @return Mage_Checkout_Model_Session|Mage_Core_Model_Abstract
     */
    public function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
