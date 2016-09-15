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
 * Mastercard_Mpgs_Model_Method_Hosted
 *
 * Hosted checkout payment method
 *
 * @package Mastercard
 * @subpackage Block
 * @author Rafael Waldo Delgado Doblas
 */
class Mastercard_Mpgs_Model_Method_Hosted extends Mastercard_Mpgs_Model_Method_Abstract {
	const METHOD_NAME = 'Mastercard_hosted';
	protected $_code = self::METHOD_NAME;
	protected $_infoBlockType = 'payment/info';

	/**
	 * Payment Method features.
	 *
	 * @var bool
	 */
	protected $_isGateway = true;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $_canCapturePartial = true;
	protected $_canRefund = true;
	protected $_canRefundInvoicePartial = true;
	protected $_canVoid = true;
	protected $_canUseInternal = false;
	protected $_canUseCheckout = true;
	protected $_canUseForMultishipping = false;
	protected $_isInitializeNeeded = false;
	private $_resultCode = '';

	/**
	 *
	 * @param array $params
	 *
	 * @return Mastercard_Mpgs_Model_Method_Abstract
	 */
	public function __construct( $params = array() ) {

		parent::__construct( $params );

		return $this;

	}

	/**
	 * Sets the result code from the MPGS payment response.
	 *
	 * @param string $resultCode
	 *
	 */
	public function setResultCode( $resultCode ) {

		$this->_resultCode = $resultCode;

	}

	protected function verifyResultCode( $payment ) {

		$successIndicator = $payment->getAdditionalInformation( "successIndicator" );
		if ($this->_resultCode != $successIndicator) {
			$helper = Mage::helper( 'mpgs' );
			Mage::throwException( $helper->maskDebugMessages( "Error successIndicator doesnt match with resultCode." ) );
		}

	}

	/**
	 * Capture the payment.
	 * This method is called when auth and capture mode is selected.
	 *
	 * @param Varien_Object $payment
	 * @param string $amount
	 * @return Mastercard_Mpgs_Model_Method_Hosted
	 * @author Rafel Waldo Delgado Doblas
	 */
	public function capture( Varien_Object $payment, $amount ) {

		parent::capture( $payment, $amount );
		$helper = Mage::helper( 'mpgs/mpgsRest' );

		$txnAuth = $payment->getAuthorizationTransaction();
		$captureInfo = $payment->getAdditionalInformation( 'webhook_info' );
		if (empty( $captureInfo )) {
			$restAPI = Mage::getSingleton( 'mpgs/mpgsApi_rest' );

			$mpgs_id = $payment->getAdditionalInformation( 'mpgs_id' );
			$orderInfo = $restAPI->retrieve_order( $mpgs_id );
			$helper->updatePaymentInfo( $payment, $orderInfo );

			$currency = $payment->getOrder()->getStore()->getBaseCurrencyCode();
			$captureInfo = $restAPI->capture_order( $mpgs_id, $amount, $currency );

			if (empty( $txnAuth )) {
				// Creates an auth txn on magento side
				$this->verifyResultCode( $payment );
				$authTxnInfo = $helper->searchTxnByType( $orderInfo, 'AUTHORIZATION' );
				$txnAuth = $helper->addAuthTxnPayment( $payment, $authTxnInfo, $helper->isAllPaid( $payment, $captureInfo ) );
			}
		}

		// Creates an capture txn on magento side
		$helper->updateTransferInfo( $payment, $captureInfo );
		$helper->addCaptureTxnPayment( $payment, $captureInfo, $txnAuth->getTxnId(), $helper->isAllPaid( $payment, $captureInfo ) );

		return $this;

	}

	/**
	 * Authorise the payment.
	 * This method is called when auth mode is selected.
	 *
	 * @param Varien_Object $payment
	 * @param string $amount
	 * @return Mastercard_Mpgs_Model_Method_Hosted
	 * @author Rafel Waldo Delgado Doblas
	 */
	public function authorize( Varien_Object $payment, $amount ) {

		$this->verifyResultCode( $payment );

		parent::authorize( $payment, $amount );

		$helper = Mage::helper( 'mpgs/mpgsRest' );
		$restAPI = Mage::getSingleton( 'mpgs/mpgsApi_rest' );

		$mpgs_id = $payment->getAdditionalInformation( 'mpgs_id' );
		$orderInfo = $restAPI->retrieve_order( $mpgs_id );
		$helper->updatePaymentInfo( $payment, $orderInfo );

		$authTxnInfo = $helper->searchTxnByType( $orderInfo, 'AUTHORIZATION' );

		$helper->updateTransferInfo( $payment, $authTxnInfo );
		$helper->addAuthTxnPayment( $payment, $authTxnInfo, false );

		return $this;

	}
}
