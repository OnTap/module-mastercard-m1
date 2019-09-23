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
class Mastercard_Mpgs_Block_Review extends Mage_Core_Block_Template
{
    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    /**
     * @var Mage_Sales_Model_Quote_Address
     */
    protected $_address;

    /**
     * @var Mage_Sales_Model_Quote_Address_Rate
     */
    protected $_currentShippingRate;

    /**
     * @var array
     */
    protected $_rates;

    /**
     * Quote object setter
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return $this
     */
    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    /**
     * Return quote shipping address
     *
     * @return Mage_Sales_Model_Quote_Address|bool
     */
    public function getShippingAddress()
    {
        if ($this->_quote->getIsVirtual()) {
            return false;
        }
        return $this->_quote->getShippingAddress();
    }

    /**
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getBillingAddress()
    {
        return $this->_quote->getBillingAddress();
    }

    /**
     * Get HTML output for specified address
     *
     * @param Mage_Sales_Model_Quote_Address
     * @return string
     */
    public function renderAddress($address)
    {
        return $address->getFormated(true);
    }

    /**
     * Get either shipping rate code or empty value on error
     *
     * @param Varien_Object $rate
     * @return string
     */
    public function renderShippingRateValue(Varien_Object $rate)
    {
        if ($rate->getErrorMessage()) {
            return '';
        }
        return $rate->getCode();
    }

    /**
     * Get shipping rate code title and its price or error message
     *
     * @param Varien_Object $rate
     * @param string $format
     * @param string $inclTaxFormat
     * @return string
     */
    public function renderShippingRateOption($rate, $format = '%s - %s%s', $inclTaxFormat = ' (%s %s)')
    {
        $renderedInclTax = '';
        if ($rate->getErrorMessage()) {
            $price = $rate->getErrorMessage();
        } else {
            $price = $this->_getShippingPrice($rate->getPrice(),
                $this->helper('tax')->displayShippingPriceIncludingTax());

            $incl = $this->_getShippingPrice($rate->getPrice(), true);
            if (($incl != $price) && $this->helper('tax')->displayShippingBothPrices()) {
                $renderedInclTax = sprintf($inclTaxFormat, Mage::helper('tax')->__('Incl. Tax'), $incl);
            }
        }
        return sprintf($format, $this->escapeHtml($rate->getMethodTitle()), $price, $renderedInclTax);
    }

    /**
     * Format price base on store convert price method
     *
     * @param float $price
     * @return string
     */
    protected function _formatPrice($price)
    {
        return $this->_quote->getStore()->convertPrice($price, true);
    }

    /**
     * Return formatted shipping price
     *
     * @param float $price
     * @param bool $isInclTax
     *
     * @return bool
     */
    protected function _getShippingPrice($price, $isInclTax)
    {
        return $this->_formatPrice($this->helper('tax')->getShippingPrice($price, $isInclTax, $this->_address));
    }

    /**
     * @return array
     */
    public function getShippingRateGroups()
    {
        if ($this->_rates) {
            return $this->_rates;
        }
        $this->_rates = $this->_address->getGroupedAllShippingRates();
        return $this->_rates;
    }

    /**
     * Retrieve payment method and assign additional template values
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->getChild('shipping')
            ->setQuote($this->_quote);

        $methodInstance = $this->_quote->getPayment()->getMethodInstance();
        $this->setPaymentMethodTitle($methodInstance->getTitle());

        $this->setShippingRateRequired(true);
        if ($this->_quote->getIsVirtual()) {
            $this->setShippingRateRequired(false);
        } else {
            // prepare shipping rates
            $this->_address = $this->_quote->getShippingAddress();
            $groups = $this->getShippingRateGroups();

            if ($groups && $this->_address) {
                // determine current selected code & name
                foreach ($groups as $code => $rates) {
                    foreach ($rates as $rate) {
                        if ($this->_address->getShippingMethod() == $rate->getCode()) {
                            $this->_currentShippingRate = $rate;
                            break(2);
                        }
                    }
                }
            }
        }

        return parent::_beforeToHtml();
    }

    /**
     * Getter for current shipping rate
     *
     * @return Mage_Sales_Model_Quote_Address_Rate
     */
    public function getCurrentShippingRate()
    {
        return $this->_currentShippingRate;
    }

    /**
     * Return carrier name from config, base on carrier code
     *
     * @param $carrierCode string
     * @return string
     */
    public function getCarrierName($carrierCode)
    {
        if ($name = Mage::getStoreConfig("carriers/{$carrierCode}/title")) {
            return $name;
        }
        return $carrierCode;
    }
}
