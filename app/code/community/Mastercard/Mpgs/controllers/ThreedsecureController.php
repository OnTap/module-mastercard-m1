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
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $quote = $this->getSession()->getQuote();
            $quote->setReservedOrderId(null)->reserveOrderId();
            $quote->save();

            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('checkout/cart/index');
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
                    'veResEnrolled' => $response['3DSecure']['veResEnrolled'],
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
