<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

class Mastercard_Mpgs_ThreedsecureController extends Mastercard_Mpgs_Controller_JsonResponseController
{
    /**
     * Process order review
     */
    public function redirectAction()
    {
        try {
            $paRes = $this->getRequest()->getParam('PaRes');
            if (!$paRes) {
                throw new Exception('Invalid or missing order data.');
            }

            $quote = $this->getSession()->getQuote();
            $payment = $quote->getPayment();

            if ($payment->getMethod() != Mastercard_Mpgs_Model_Method_Form::METHOD_NAME) {
                throw new Exception('Payment method not available.');
            }

            $payment
                ->setAdditionalInformation('PaRes', $paRes)
                ->save();

            $quote->collectTotals();

            $this->getOnepage()->saveOrder();
            $quote->save();

            $this->_redirect('checkout/onepage/success');

        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('checkout/cart/index');
        }
    }

    /**
     * Check for card enrolment in 3DS
     */
    public function enrolmentAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->sendErrorResponse('403 Invalid Form Key');
        }

        $requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
        if ($requiredAgreements) {
            $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
            $diff = array_diff($requiredAgreements, $postedAgreements);
            if ($diff) {
                $this->_prepareDataJSON(array(
                    'error' => $this->__('Please agree to all the terms and conditions before placing the order.')
                ));
                return;
            }
        }

        $payment = new Varien_Object(array(
            'method' => Mastercard_Mpgs_Model_Method_Form::METHOD_NAME
        ));

        $quote = $this->getSession()->getQuote();

        try {
            /** @var Mastercard_Mpgs_Model_MpgsApi_Rest $restAPI */
            $restAPI = Mage::getSingleton('mpgs/restFactory')->get($payment);
            $content = null;
            $payment = $quote->getPayment();

            try {
                $response = $restAPI->check_3ds_enrolment(
                    $quote->getPayment()->getData('mpgs_session_id'),
                    $quote,
                    Mage::getUrl('mastercard/threedsecure/redirect', array('_secure' => true))
                );

                $data = array(
                    'status' => $response['3DSecure']['summaryStatus'],
                    'xid' => $response['3DSecure']['xid'],
                );

                $payment->setAdditionalInformation('3DSecureEnrollment', $data);
                $payment->setAdditionalInformation('3DSecureId', $response['3DSecureId']);
                $payment->save();

                if (isset($response['3DSecure']['authenticationRedirect'])) {
                    $content = $response['3DSecure']['authenticationRedirect']['simple']['htmlBodyContent'];
                }
            } catch (Mastercard_Mpgs_Model_MpgsApi_Validator_NotEnrolledException $e) {
                $payment
                    ->setAdditionalInformation('3DSecureNotEnrolled', true)
                    ->save();
            }

            $this->_prepareDataJSON(array(
                '3DSecureBodyContent' => $content
            ));

        } catch (Exception $e) {
            $this->sendErrorResponse('502 3DSecure Error', $e);
        }
    }

    /**
     * @param string $header
     */
    protected function sendErrorResponse($header = '503 Service Unavailable', $e = null)
    {
        $response = $this->getResponse()
            ->setHeader('HTTP/1.1', $header);

        if ($e) {
            $response->renderExceptions(Mage::getIsDeveloperMode());
            $response->setException($e);
        }

        $response->sendResponse();
    }
}
