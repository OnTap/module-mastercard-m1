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

class Mastercard_Mpgs_OrderController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    /**
     * Clear reserved order ID for current quote
     * @return Mage_Core_Controller_Varien_Action
     */
    public function cancelAction()
    {
        $this->getOnepage()->getQuote()->setReservedOrderId(null)->save();

        $this->getSession()->addError($this->__('Payment cancelled'));
        return $this->_redirect('checkout/cart/index', array('_secure' => true));
    }
}
