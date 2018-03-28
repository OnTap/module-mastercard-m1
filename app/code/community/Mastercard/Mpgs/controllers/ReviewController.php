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
            $quote = $this->getOnepage()->getQuote();
            $quote->collectTotals();

            $this->getOnepage()->saveOrder();
            $quote->save();

            $this->_redirect('checkout/onepage/success');

        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Review Page
     */
    public function indexAction()
    {
//        try {
            $quote = $this->getQuote();
            $payment = $quote->getPayment();

            /** @var Mastercard_Mpgs_Model_Method_WalletInterface $method */
            $method = $payment->getMethodInstance();
            if ($method instanceof Mastercard_Mpgs_Model_Method_WalletInterface) {
                $method->getAddressDataFromSession($payment);
            }

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
                ->setShippingMethod($shippingMethod)
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

//        } catch (Mage_Core_Exception $e) {
//            Mage::getSingleton('checkout/session')->addError($e->getMessage());
//        }  catch (Exception $e) {
//            Mage::getSingleton('checkout/session')->addError(
//                $this->__('Unable to initialize summary page.')
//            );
//            Mage::logException($e);
//        }
//
//        $this->_redirect('checkout/cart');
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
