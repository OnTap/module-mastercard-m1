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
class Mastercard_Mpgs_Block_Checkout_Button_Hosted extends Mastercard_Mpgs_Block_Checkout_Button_AbstractButton
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->setTemplate('Mastercard/checkout/button/hosted.phtml');
        parent::_construct();
    }

    /**
     * @return string
     */
    protected function getStoreName()
    {
        $name = Mage::getStoreConfig('general/store_information/name');
        if (!$name) {
            $name = 'Magento Store';
        }
        return (string) $name;
    }

    /**
     * @return string
     */
    public function getJsConfig()
    {
        $config = Mage::getSingleton('mpgs/config_hosted');
        return json_encode(
            array(
                'component_url' => $config->getJsComponentUrl(),
                'store_name' => $this->jsQuoteEscape($this->getStoreName(), '"'),
                'cart_id' => $this->getQuote()->getId(),
                'create_session_url' => Mage::getUrl('mastercard/checkout/createSession', array('_secure' => true)),
                'merchant' => $config->getApiUsername(),
                'cancel_url' => Mage::getUrl('mastercard/order/cancel', array('_secure' => true)),
            )
        );
    }
}
