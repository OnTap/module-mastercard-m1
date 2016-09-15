<?php
/**
 * Mastercard
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@Mastercard.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://testserver.Mastercard.com/software/download.cgi
 * for more information.
 *
 * @author Rafael Waldo Delgado Doblas
 * @version $Id$
 * @copyright Mastercard, 1 Jul, 2016
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @package Mastercard
 **/

/**
 * Mastercard_Mpgs_WebhookController
 *
 * This class allow to prepare invoices without items
 *
 * @package Mastercard
 * @subpackage Controllers
 * @author Rafael Waldo Delgado Doblas
 */
class Mastercard_Mpgs_Model_Service_Order extends Mage_Sales_Model_Service_Order {

	/**
	 * Prepare order invoice based on order data and requested totals.
	 * If txnAmount is equal to the order GrandTotal a normal invoice will
	 * be issued if not and invoice with no items will be issued and the totals
	 * till be based on $totals array.
	 *
	 * @param array $totals
	 * @return Mage_Sales_Model_Order_Invoice
	 */
	public function prepareInvoice( $totals ) {

		if ( $totals['txnAmount'] == $this->_order->getGrandTotal() ) {
			return parent::prepareInvoice();
		}

		$invoice = $this->_convertor->toInvoice( $this->_order );

		$invoice->setTotalQty( 0 );

		$invoice->setSubtotal( $totals ['txnAmount'] - $totals ['txnTaxAmount'] );
		$invoice->setBaseSubtotal( $totals ['txnAmount'] - $totals ['txnTaxAmount'] );

		$invoice->setTaxAmount( $totals ['txnTaxAmount'] );
		$invoice->setBaseTaxAmount( $totals ['txnTaxAmount'] );

		$invoice->setGrandTotal( $invoice->getGrandTotal() + $totals ['txnAmount'] );
		$invoice->setBaseGrandTotal( $invoice->getBaseGrandTotal() + $totals ['txnAmount'] );
		$invoice->addComment( __( 'Partial Capture from MPGS received. Invoicing with no items.' ) );

		$this->_order->getInvoiceCollection()->addItem( $invoice );
		$this->_order->addStatusHistoryComment( __( 'Partial Capture from MPGS received. Invoicing from magento disabled.' ) );

		return $invoice;

	}

}
