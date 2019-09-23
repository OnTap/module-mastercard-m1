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

class Mastercard_Mpgs_CheckoutController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    /**
     * Create Session Action
     * @throws Mage_Core_Exception
     * @throws Exception
     */
    public function createSessionAction()
    {
        $cartId = $this->getRequest()->getParam('cartId');

        $quote = $this->getQuote();

        if ($cartId != $quote->getId()) {
            Mage::throwException('Cart ID not found');
        }

        $quote->reserveOrderId();
        $quote->save();

        /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
        $restAPI = Mage::getSingleton(
            'mpgs/mpgsApi_rest', array(
                'config' => Mage::getSingleton('mpgs/config_hosted')
            )
        );

        $resData = $restAPI->create_checkout_session($quote->getReservedOrderId(), $quote);

        $payment = $quote->getPayment();
        $payment->setAdditionalInformation('successIndicator', $resData['successIndicator']);
        $payment->setAdditionalInformation('mpgs_id', $quote->getReservedOrderId());
        $payment->save();

        $dataOut = array();
        $dataOut['merchant'] = $resData['merchant'];
        $dataOut['SessionID'] = $resData['session']['id'];
        $dataOut['SessionVersion'] = $resData['session']['version'];

        $this->_prepareDataJSON($dataOut);
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/type_onepage')->getQuote();
    }
}
