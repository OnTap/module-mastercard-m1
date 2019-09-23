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
class Mastercard_Mpgs_Model_Token extends Varien_Object
{
    /**
     * @return string
     */
    public function asJson()
    {
        return json_encode(array(
            'token' => $this->getToken(),
            'card' => $this->getCard(),
            'expire' => $this->getExpire()
        ));
    }

    /**
     * @param array $response
     * @return $this
     */
    public function createTokenFromResponse($response)
    {
        $this->setToken($response['token']);

        $card = $response['sourceOfFunds']['provided']['card'];
        $this->setCard(array(
            'maskedCc' => $card['number'],
            'scheme' => $card['scheme'],
            'expiry' => $card['expiry'],
            'brand' => $card['brand']
        ));

        $expiryMonth = substr($card['expiry'], 0, 2);
        $expiryYear = substr($card['expiry'], 2);

        $lastDay = date('t', strtotime(sprintf('%s-%s-1', $expiryYear, $expiryMonth)));
        $this->setExpire(strtotime(sprintf('%s-%s-%s 00:00:00', $expiryYear, $expiryMonth, $lastDay)));

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->getExpire() && time() < $this->getExpire();
    }

    /**
     * @param Mage_Customer_Model_Customer|string $customer
     * @return $this
     */
    public function getFromCustomer($customer)
    {
        if (!is_object($customer) && is_numeric($customer)) {
            $customer = Mage::getModel('customer/customer')->load($customer);
        }

        $raw = $customer->getData('mpgs_card_token');
        $data = json_decode($raw, true);

        $this->setToken($data['token']);
        $this->setCard($data['card']);
        $this->setExpire($data['expire']);

        return $this;
    }
}
