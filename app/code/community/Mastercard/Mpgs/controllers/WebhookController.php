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
 * Controller to handle Webhook Notifications from MPGS
 *
 * @package Mastercard
 * @subpackage Controllers
 * @author Rafael Waldo Delgado Doblas
 */
class Mastercard_Mpgs_WebhookController extends Mage_Core_Controller_Front_Action
{
    const X_HEADER_SECRET = 'X-Notification-Secret';
    const X_HEADER_ATTEMPT = 'X-Notification-Attempt';
    const X_HEADER_ID = 'X-Notification-Id';

    /**
     * This method will create an invoice.
     * This method will be called once a full capture notification is received
     * to create an invoice (that means capture in magento) to match the order status on target platform. The invoice
     * will be created online so a transaction is added to the order but actully will not hit MPGS again.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $txnInfo
     *
     */
    protected function createInvoice( $order, $txnInfo ) 
    {

        $totals ['txnAmount'] = $txnInfo ['transaction'] ['amount'];
        $totals ['txnTaxAmount'] = $txnInfo ['transaction'] ['taxAmount'];
        $invoice = Mage::getModel('mpgs/service_order', $order)->prepareInvoice($totals);

        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->getOrder()->getPayment()->setAdditionalInformation('webhook_info', $txnInfo);
        $invoice->register();
        $invoice->getOrder()->getPayment()->setAdditionalInformation('webhook_info', null);

        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

    }

    /**
     * This method will create a credit memo.
     * This method will be called once a full refund notification is received
     * to create a credit memo (that means refund in magento) to match the order status on target platform. The credit
     * memo will be created online so a transaction is added to the order but actully will not hit MPGS again.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $txnInfo
     *
     */
    protected function createCreditMemo( $order, $txnInfo ) 
    {

        if (! $order->canCreditmemo()) {
            Mage::throwException(Mage::helper('core')->__('Cannot create a credit memo.'));
        }

        $transactionSave = Mage::getModel('core/resource_transaction');
        $order->getPayment()->setAdditionalInformation('webhook_info', $txnInfo);

        $invoices = Mage::getResourceModel('sales/order_invoice_collection');
        $invoices = $invoices->setOrderFilter($order)->addFieldToFilter('state', Mage_Sales_Model_Order_Invoice::STATE_PAID);

        if ($txnInfo ['transaction'] ['amount'] == $order->getGrandTotal()) {
            // Full Refund
            foreach ($invoices as $invoice) {
                if (! $invoice->getTransactionId()) {
                    continue;
                }

                if (! $invoice->canRefund()) {
                    $this->disableRefund($order, 'Full refund from gateway received, on an order with one or more invoices refunded. Disabling online refunds.');
                    Mage::throwException(Mage::helper('core')->__('Cannot refund the invoice.'));
                }

                // If invoice without items then use adjustment amount
                if ($invoice->getTotalQty() == 0) {
                    $data ['adjustment_positive'] = $txnInfo ['transaction'] ['amount'];
                }

                $creditMemo = Mage::getModel('sales/service_order', $order)->prepareInvoiceCreditmemo($invoice, $data);
                $creditMemo->setRefundRequested(true)->setOfflineRequested(false)->register(); // request to refund online
                $transactionSave->addObject($creditMemo)->addObject($creditMemo->getOrder())->addObject($creditMemo->getInvoice());
            }
        } else {
            // Partial Refund
            // If there is more than one invoice we can not know which invoce has been refunded.
            if (count($invoices) !== 1) {
                $this->disableRefund($order, 'Partial refund from gateway received, on an order with multiple invoices. Disabling online refunds.');
                Mage::throwException(Mage::helper('core')->__('Cannot create a credit memo.'));
            }

            $invoice = $invoices->getFirstItem();
            if (! $invoice->canRefund()) {
                Mage::throwException(Mage::helper('core')->__('Cannot refund the invoice.'));
            }

            $qtys = array ();
            foreach ($invoice->getAllItems() as $orderItemId => $itemData) {
                $qtys [$orderItemId] = 0;
            }

            $data ['qtys'] = $qtys;
            $data ['adjustment_positive'] = $txnInfo ['transaction'] ['amount'];
            $order->addStatusHistoryComment(__('Partial refund from gateway received, refund with no items will be performed.'));
            $creditMemo = Mage::getModel('sales/service_order', $order)->prepareInvoiceCreditmemo($invoice, $data);
            $creditMemo->addComment(__('Partial refund from gateway received, refund with no items will be performed.'));
            $creditMemo->setRefundRequested(true)->setOfflineRequested(false)->register(); // request to refund online
            $transactionSave->addObject($creditMemo)->addObject($creditMemo->getOrder())->addObject($creditMemo->getInvoice());
        }

        $order->getPayment()->setAdditionalInformation('webhook_info', null);
        $transactionSave->save();

    }

    /**
     * Voids an order in auth state.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     */
    protected function voidAuth( $order, $txnInfo ) 
    {

        $order->getPayment()->setAdditionalInformation('webhook_info', $txnInfo);
        $order->getPayment()->void();
        $order->getPayment()->setAdditionalInformation('webhook_info', null);

        Mage::getModel('core/resource_transaction')->addObject($order)->addObject($order->getPayment())->save();

    }

    /**
     * Voids a capture.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     */
    protected function voidCapture( $order, $txnInfo ) 
    {

        $txn_id = $txnInfo ['transaction'] ['targetTransactionId'];
        $invoices = Mage::getResourceModel('sales/order_invoice_collection');
        $invoices = $invoices->setOrderFilter($order)->addFieldToFilter('transaction_id', $txn_id);

        // The filter should return a unique invoice.
        if (count($invoices) !== 1) {
            Mage::throwException(Mage::helper('core')->__('Cannot void invoice.'));
        }

        $invoice = $invoices->getFirstItem();

        // If this invoice was used for refund we should not void it.
        if ($invoice->getIsUsedForRefund()) {
            Mage::throwException(Mage::helper('core')->__('Invoice used for refund.'));
        }

        // If the invoice was already void exit.
        if ($invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_CANCELED) {
            Mage::throwException(Mage::helper('core')->__('Invoice already void.'));
        }

        $invoice->cancel();

        // Reopening order since the transaction is not fully capture anymore.
        $invoice->getOrder()->setIsInProcess(true);
        $invoice->getOrder()->setState(Mage_Sales_Model_Order::STATE_NEW);
        $invoice->getOrder()->addStatusHistoryComment(__('Void capture received, canceling invoice.'));

        $authTransaction = $invoice->getOrder()->getPayment()->getAuthorizationTransaction()->setIsClosed(0);

        $helper = Mage::helper('mpgs/mpgsRest');
        $helper->updateTransferInfo($invoice->getOrder()->getPayment(), $txnInfo);
        $helper->addVoidTxnPayment($invoice->getOrder()->getPayment(), $txnInfo, $txn_id);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->addObject($invoice->getOrder()->getPayment())
            ->addObject($authTransaction)
            ->save();

    }

    /**
     * Voids a refund.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     */
    protected function voidRefund( $order, $txnInfo ) 
    {

        $txn_id = $txnInfo ['transaction'] ['targetTransactionId'];
        $creditMemos = Mage::getResourceModel('sales/order_creditmemo_collection');
        $creditMemos = $creditMemos->setOrderFilter($order)->addFieldToFilter('transaction_id', $txn_id);

        if (count($creditMemos) === 0) {
            Mage::throwException(Mage::helper('core')->__('Cannot void credit memo.'));
        }

        $first = true;
        $transactionSave = Mage::getModel('core/resource_transaction');
        foreach ($creditMemos as $creditMemo) {
            // If the credit memo was already void exit.
            if ($creditMemo->getState() == Mage_Sales_Model_Order_Creditmemo::STATE_CANCELED) {
                Mage::throwException(Mage::helper('core')->__('Credit memo already void.'));
            }

            $creditMemo->cancel();
            $newTotalRefunded = $creditMemo->getOrder()->getTotalRefunded() - $creditMemo->getGrandTotal();
            $creditMemo->getOrder()->setTotalRefunded($newTotalRefunded);
            $newBaseTotalRefunded = $creditMemo->getOrder()->getBaseTotalRefunded() - $creditMemo->getBaseGrandTotal();
            $creditMemo->getOrder()->setBaseTotalRefunded($newBaseTotalRefunded);

            // Reopening order since the transaction is not fully refunded anymore.
            $creditMemo->getOrder()->setIsInProcess(true);
            $creditMemo->getOrder()->setState(Mage_Sales_Model_Order::STATE_NEW);

            foreach ($creditMemo->getAllItems() as $item) {
                $item->getOrderItem()->setAmountRefunded($item->getOrderItem()->getAmountRefunded() - $creditMemo->getGrandTotal());
                $item->getOrderItem()->setBaseAmountRefunded($item->getOrderItem()->getBaseAmountRefunded() - $creditMemo->getGrandTotal());
            }

            $invoice = Mage::getModel('sales/order_invoice')->load($creditMemo->getInvoiceId());
            if (! empty($invoice)) {
                $newInvoiceRefunded = $invoice->getBaseTotalRefunded() - $creditMemo->getBaseGrandTotal();
                $invoice->setBaseTotalRefunded($newInvoiceRefunded);
                if ($newInvoiceRefunded < 0.001) {
                    $invoice->setIsUsedForRefund(false);
                }
            }

            if ($first) {
                $first = false;
                $helper = Mage::helper('mpgs/mpgsRest');
                $helper->updateTransferInfo($creditMemo->getOrder()->getPayment(), $txnInfo);
                $helper->addVoidTxnPayment($creditMemo->getOrder()->getPayment(), $txnInfo, $txn_id);
                $creditMemo->getOrder()->addStatusHistoryComment(__('Void refund received, canceling creditmemo.'));
            }

            $transactionSave
                ->addObject($creditMemo)
                ->addObject($creditMemo->getOrder())
                ->addObject($creditMemo->getOrder()->getPayment())
                ->addObject($invoice);
        }

        $transactionSave->save();

    }

    /**
     *
     * Update order information with the information provided by MPGS Webhook Notification.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $data
     * @param string $headerid
     */
    protected function updateOrderDetails( $order, $txnInfo, $headerid ) 
    {

        $payment = $order->getPayment();
        $helper = Mage::helper('mpgs/mpgsRest');

        $order->addStatusHistoryComment(sprintf(__('Order updated by gateway [ID: %s]'), $headerid));

        switch ($txnInfo ['transaction'] ['type']) {
            case 'CAPTURE' :
                $this->createInvoice($order, $txnInfo);
                break;
            case 'REFUND' :
                $this->createCreditMemo($order, $txnInfo);
                break;
            case 'VOID_AUTHORIZATION' :
                $this->voidAuth($order, $txnInfo);
                break;
            case 'VOID_CAPTURE' :
                $this->voidCapture($order, $txnInfo);
                break;
            case 'VOID_REFUND' :
                $this->voidRefund($order, $txnInfo);
                break;
        }

    }

    protected function disableRefund( $order, $msg ) 
    {

        $order->getPayment()->setAdditionalInformation('disableRefund', '1');
        $order->addStatusHistoryComment(__($msg));
        $order->save();

    }

    /**
     *
     * Check if the transaction is already present on the order.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $txnId
     *
     * @return boolean
     *
     */
    protected function isTxnIdPresent( $order, $txnId ) 
    {

        $transactions = Mage::getModel('sales/order_payment_transaction')
            ->getCollection()
            ->addAttributeToFilter('order_id', $order->getEntityId())
            ->addAttributeToFilter('txn_id', $txnId);
        return count($transactions) > 0;

    }

    /**
     * Fetch a order object by MPGS Ref.
     *
     * @param string $mpgsRef
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     *
     * @throws NoSuchEntityException
     */
    protected function getOrderbyMpgsRef( $mpgsId, $mpgsRef ) 
    {

        $helper = Mage::helper('mpgs');

        // Find the order
        $orders = Mage::getModel('sales/order')->getCollection()->addAttributeToFilter('increment_id', $mpgsRef);
        if (count($orders) < 1 || count($orders) > 1) {
            throw new NoSuchEntityException(__($helper->maskDebugMessages('Could not find order.')));
        }

        $order = $orders->getFirstItem();

        // Verify that the order correspoding to the reference is the one that correspond to the MPGS Id to avoid order spoofing.
        if ($order->getPayment()->getAdditionalInformation('mpgs_id') != $mpgsId) {
            throw new NoSuchEntityException(__($helper->maskDebugMessages('MPGS Id Missmatch.')));
        }

        return $order;

    }

    protected function validateConnectionDetails( $request ) 
    {

        if (! $request->isSecure()) {
            throw new \Exception(__('Secure connection required.'));
        }

        $headerid = $this->getRequest()->getHeader(static::X_HEADER_ID);
        if (empty($headerid)) {
            throw new \Exception(__('Header ID not provided'));
        }

        $requestSecret = $request->getHeader(static::X_HEADER_SECRET);
        if (empty($requestSecret)) {
            throw new \Exception(__('Authorization not provided'));
        }

        $WebhookSecret = Mage::getSingleton('mpgs/config')->getWebhookSecret();
        if (empty($WebhookSecret)) {
            throw new \Exception(__('Webhook Disabled'));
        }

        if ($WebhookSecret !== $requestSecret) {
            throw new \Exception(__('Authorization failed'));
        }

    }

    protected function validateWebhookInfo( $txnInfo ) 
    {

        if (! isset($txnInfo ['transaction']) || ! isset($txnInfo ['transaction'] ['type'])) {
            throw new \Exception(__('Invalid data received (Transaction Type)'));
        }

        if (! isset($txnInfo ['transaction']) || ! isset($txnInfo ['transaction'] ['id'])) {
            throw new \Exception(__('Invalid data received (Transaction Id)'));
        }

        if (! isset($txnInfo ['order']) || ! isset($txnInfo ['order'] ['id'])) {
            throw new \Exception(__('Invalid data received (Order ID)'));
        }

    }

    /**
     *
     * Decodes the json data from MPGS Webhook Notification request.
     *
     * @return array
     *
     * @throws \Zend_Json_Exception
     */
    protected function getData() 
    {

        return \Zend_Json_Decoder::decode($this->getRequest()->getRawBody(), \Zend_Json::TYPE_ARRAY);

    }

    /**
     * Dispatch MPGS Webhook Notification request.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     *
     */
    public function updateAction() 
    {

        $request = $this->getRequest();
        $response = $this->getResponse();

        try {
            $this->validateConnectionDetails($request);

            $txnInfo = $this->getData();
            $this->validateWebhookInfo($txnInfo);

            $restAPI = Mage::getSingleton('mpgs/mpgsApi_rest');
            $mpgsId = $txnInfo ['order'] ['id'];
            $txnId = $txnInfo ['transaction'] ['id'];
            $txnInfo = $restAPI->retrieve_transaction($mpgsId, $txnId);

            $order = $this->getOrderbyMpgsRef($mpgsId, $txnInfo ['order'] ['reference']);

            $headerid = $this->getRequest()->getHeader(static::X_HEADER_ID);

            // Check if the transaction was already dispachet to avoid double notifications.
            // No exeption trowed because this is a normal situation in Internet where retries can be attempted.
            if (! $this->isTxnIdPresent($order, $txnInfo ['transaction'] ['id'])) {
                // Proccess the webhook info
                $this->updateOrderDetails($order, $txnInfo, $headerid);
            }
        } catch (\Exception $e) {
            $errorMessage = sprintf(__("Mastercard Mpgs WebHook Exception: '%s'"), $e->getMessage());
            $response->setHttpResponseCode(400);

            $helper = Mage::helper('mpgs');
            return $response->setBody($helper->maskDebugMessages($errorMessage));
        }

        $response->setHttpResponseCode(200);

        return $response->setBody('');

    }
}
