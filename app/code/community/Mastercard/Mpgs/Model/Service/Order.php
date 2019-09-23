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
class Mastercard_Mpgs_Model_Service_Order extends Mage_Sales_Model_Service_Order
{
    /**
     * Prepare order invoice based on order data and requested totals.
     * If txnAmount is equal to the order GrandTotal a normal invoice will
     * be issued if not and invoice with no items will be issued and the totals
     * till be based on $totals array.
     *
     * @param array $totals
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function prepareInvoice($totals = array())
    {
        if ($totals['txnAmount'] == $this->_order->getGrandTotal()) {
            return parent::prepareInvoice();
        }

        $invoice = $this->_convertor->toInvoice($this->_order);

        $invoice->setTotalQty(0);

        $invoice->setSubtotal($totals ['txnAmount'] - $totals ['txnTaxAmount']);
        $invoice->setBaseSubtotal($totals ['txnAmount'] - $totals ['txnTaxAmount']);

        $invoice->setTaxAmount($totals ['txnTaxAmount']);
        $invoice->setBaseTaxAmount($totals ['txnTaxAmount']);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $totals ['txnAmount']);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $totals ['txnAmount']);
        $invoice->addComment(__('Partial Capture from MPGS received. Invoicing with no items.'));

        $this->_order->getInvoiceCollection()->addItem($invoice);
        $this->_order->addStatusHistoryComment(__('Partial Capture from MPGS received. Invoicing from magento disabled.'));

        return $invoice;
    }
}
