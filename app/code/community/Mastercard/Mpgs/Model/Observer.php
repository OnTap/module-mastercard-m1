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

class Mastercard_Mpgs_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function checkNotificationsPreDispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $feedModel  = Mage::getModel('mpgs/feed');
            /* @var $feedModel Mastercard_Mpgs_Model_Feed */

            $feedModel->checkUpdate();
        }
    }

    /**
     * This method updates the result code from a MPGS payment reponse.
     *
     * @param Varien_Event_Observer $observer
     */
    public function updateResultCode( $observer ) 
    {
        $payment = $observer->getEvent()->getPayment();
        if ($payment->getMethod() === Mastercard_Mpgs_Model_Method_Hosted::METHOD_NAME) {
            $payment->getMethodInstance()->setResultCode(Mage::app()->getRequest()->getParam('res_code'));
        }
    }

    /**
     * This method updates the payment section on the order details with additional information from MPGS.
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function paymentInfoBlockPrepareSpecificInformation($observer)
    {
        if ($observer->getEvent()->getBlock()->getIsSecureMode()) {
            return $this;
        }

        // If the payment method is not MPGS return
        $payment = $observer->getEvent()->getPayment();
        if (! $payment->getMethodInstance() instanceof Mastercard_Mpgs_Model_Method_Abstract) {
            return $this;
        }

        $transport = $observer->getEvent()->getTransport();

        $helper = Mage::helper('mpgs/mpgsRest');
        $info = array (
            'response_gatewayCode',
            'txn_result',
            'auth_code',
            'card_number',
            'card_expiry_date',
            'fundingMethod',
            'issuer',
            'nameOnCard',
            'cvv_validation',
            '3dsecure_authenticationStatus',
            '3dsecure_enrollmentStatus',
        );

        foreach ($info as $key) {
            if ($value = $payment->getAdditionalInformation($key)) {
                $transport->setData($helper->getLabel($key), $value);
            }
        }

        $riskinfo = array (
                'response.gatewayCode',
                'response.review.decision',
                'response.totalScore'
        );

        $risk = $payment->getAdditionalInformation('risk');
        foreach ($riskinfo as $key) {
            if (isset($risk [$key])) {
                $value = "'$risk[$key]'";
                $transport->setData($helper->getLabel($key), $value);
            }
        }

        $orderinfo = array (
                ".status",
                ".totalAuthorizedAmount",
                ".totalCapturedAmount",
                ".totalRefundedAmount"
        );

        $order = $payment->getAdditionalInformation('order');
        foreach ($orderinfo as $key) {
            if (isset($order [$key])) {
                $value = "'$order[$key]'";
                $transport->setData($helper->getLabel($key), $value);
            }
        }

        return $this;
    }

    /**
     * This method disables the Cancel action if a capture has been performed and the payment method is MPGS
     * Also the Invoice action will be disabled if there is an invoice with no items.
     *
     * @param Varien_Event_Observer $observer
     */
    public function addExtraInfoOrder( $observer ) 
    {
        $order = $observer->getOrder();
        $payment = $observer->getOrder()->getPayment();

        if (empty($payment) || ! $payment->getMethodInstance() instanceof Mastercard_Mpgs_Model_Method_Abstract) {
            return;
        }

        $totalPaid = $order->getTotalPaid();
        $isCaptured = ! empty($totalPaid);
        if ($isCaptured) {
            $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);

            $invoiceCollection = $order->getInvoiceCollection();
            foreach ($invoiceCollection as $invoice) {
                if ($invoice->getTotalQty() == 0) {
                    $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE, false);
                    break;
                }
            }
        }
    }
}
