<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Model_Api2_Restapi_Rest_Guest_V1 extends Mastercard_Mpgs_Model_Api2_Restapi
{
    /**
     * This method configures and creates the checkout session
     *
     * @param array $data
     * @return array $dataOut
     */
    public function _create( array $data )
    {
        if (empty($data ['cartid'])) {
            $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
        }

        $quote = Mage::getModel('sales/quote')->loadByIdWithoutStore($data ['cartid']);

        $quote->reserveOrderId();
        $quote->save();

        $restAPI = Mage::getSingleton('mpgs/mpgsApi_rest', array(
            'config' => Mage::getSingleton('mpgs/config_hosted')
        ));
        $mpgs_id = uniqid(sprintf('%s-', ( string ) $quote->getReservedOrderId()));
        $resData = $restAPI->create_checkout_session($mpgs_id, $quote);

        $payment = $quote->getPayment();
        $payment->setAdditionalInformation('successIndicator', $resData ['successIndicator']);
        $payment->setAdditionalInformation('mpgs_id', $mpgs_id);
        $payment->save();

        $dataOut ['merchant'] = $resData ['merchant'];
        $dataOut ['SessionID'] = $resData ['session'] ['id'];
        $dataOut ['SessionVersion'] = $resData ['session'] ['version'];

        return $dataOut;
    }

    /**
     * Dispatchs the api calls.
     *
     * @return string
     */
    public function dispatch() 
    {
        switch ($this->getActionType() . $this->getOperation()) {
            /* Create */
            case self::ACTION_TYPE_COLLECTION . self::OPERATION_CREATE :
                // If no of the methods(multi or single) is implemented, request body is not checked
                $this->_errorIfMethodNotExist('_create');

                // If creation method is implemented, request body must not be empty
                $requestData = $this->getRequest()->getBodyParams();
                if (empty($requestData)) {
                    $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
                }

                // The create action has the dynamic type which depends on data in the request body
                if ($this->getRequest()->isAssocArrayInRequestBody()) {
                    $filteredData = $this->getFilter()->in($requestData);
                    if (empty($filteredData)) {
                        $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
                    }

                    $retrievedData = $this->_create($filteredData);
                    $filteredDataOut = $this->getFilter()->out($retrievedData);
                    $this->_render($filteredDataOut);
                } else {
                    $this->_critical(self::RESOURCE_METHOD_NOT_IMPLEMENTED);
                }
                break;
            default :
                $this->_critical(self::RESOURCE_METHOD_NOT_IMPLEMENTED);
                break;
        }
    }
}
