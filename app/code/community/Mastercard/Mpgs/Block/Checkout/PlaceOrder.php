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
class Mastercard_Mpgs_Block_Checkout_PlaceOrder extends Mage_Payment_Block_Form_Cc
{
    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/type_onepage')->getQuote();
    }

    /**
     * Set method info
     *
     * @return $this
     */
    public function setMethodInfo()
    {
        $payment = $this->getQuote()
            ->getPayment();

        $this->setMethod($payment->getMethodInstance());

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function _toHtml()
    {
        $method = $this->getMethod();
        $renderer = $method->getButtonRenderer();

        if (!$renderer) {
            return '';
        }

        $block = $this->getLayout()->createBlock(
            $renderer,
            $this->getNameInLayout() . '.renderer',
            array(
                'quote' => $this->getQuote()
            )
        );

        if (!$block) {
            throw new Exception(sprintf('MPGS renderer block class "%s" not found', $renderer));
        }

        $this->setChild('button', $block);

        return parent::_toHtml();
    }

    /**
     * @return Mage_Core_Block_Abstract
     */
    public function _prepareLayout()
    {
        $this->setMethodInfo();
        return parent::_prepareLayout();
    }
}
