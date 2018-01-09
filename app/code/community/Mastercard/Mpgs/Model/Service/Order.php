<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
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
