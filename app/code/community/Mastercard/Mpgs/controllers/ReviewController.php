<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */

class Mastercard_Mpgs_ReviewController extends Mage_Core_Controller_Front_Action
{
    /**
     * Place Order
     */
    public function placeOrderAction()
    {
        try {
            $requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
            if ($requiredAgreements) {
                $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                $diff = array_diff($requiredAgreements, $postedAgreements);
                if ($diff) {
                    throw new Exception($this->__('Please agree to all the terms and conditions before placing the order.'));
                }
            }

            $quote = $this->getOnepage()->getQuote();
            $quote->collectTotals();

            $this->getOnepage()->saveOrder();
            $quote->save();

            $this->_redirect('checkout/onepage/success');

        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('mastercard/review/index');
        }
    }

    /**
     * Review Page
     */
    public function indexAction()
    {
        try {
            $quote = $this->getQuote();
            $payment = $quote->getPayment();

            $addressesExported = (bool) $payment->getAdditionalInformation('addresses_exported');

            /** @var Mastercard_Mpgs_Model_Method_WalletInterface $method */
            $method = $payment->getMethodInstance();
            if ($method instanceof Mastercard_Mpgs_Model_Method_WalletInterface && !$addressesExported) {
                $method->getAddressDataFromSession($payment);
                $payment->setAdditionalInformation('addresses_exported', 1);

                $quote->getShippingAddress()
                    ->setCollectShippingRates(true)
                    ->collectShippingRates()
                    ->save();

                $shippingMethod = null;
                foreach ($quote->getShippingAddress()->getGroupedAllShippingRates() as $group) {
                    foreach ($group as $rate) {
                        $shippingMethod = $rate->getCode();
                        break;
                    }
                }

                $quote->getShippingAddress()
                    ->setShippingMethod($shippingMethod);
            }

            $quote
                ->getShippingAddress()
                ->setCollectShippingRates(true);
            $quote
                ->collectTotals()
                ->save();

            $this->loadLayout();

            /** @var Mastercard_Mpgs_Block_Review $block */
            $block = $this->getLayout()->getBlock('checkout.review');
            $block->setQuote($quote);

            $this->getLayout()->getBlock('head')->setTitle($this->__('Order Summary'));
            $this->renderLayout();

            return;

        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        }  catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError(
                $this->__('Unable to initialize order review page.')
            );
            Mage::logException($e);
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getOnepage()->getQuote();
    }

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }
}
