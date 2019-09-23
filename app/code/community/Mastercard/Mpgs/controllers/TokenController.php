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

class Mastercard_Mpgs_TokenController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    /**
     * Remove token from customer account
     * @throws Exception
     */
    public function removeAction()
    {
        /** @var Mage_Customer_Model_Session $session */
        $session = Mage::getSingleton('customer/session');

        if ($session->isLoggedIn()) {
            $customer = Mage::getModel('customer/customer')->load($session->getCustomerId());
            $customer
                ->setData('mpgs_card_token', '')
                ->save();

            $this->_prepareDataJSON(array());
        } else {
            $this->getResponse()
                ->setHeader('HTTP/1.1', '502 Error Deleting Token')
                ->sendResponse();
        }
    }
}
