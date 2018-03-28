<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Cart_Button_Amex_Direct extends Mastercard_Mpgs_Block_Cart_Button_AbstractButton
{
    /**
     * @return string
     */
    public function getJsConfig()
    {
        $params = array(
            '_secure' => true,
            'method' => Mastercard_Mpgs_Model_Method_Amex::METHOD_NAME
        );

        return json_encode(
            array(
                'component_url' => $this->getConfig()->getComponentUrl(),
                'save_payment_url' => Mage::getUrl('mastercard/session/setPaymentInformation', $params),
                'wallet_url' => Mage::getUrl('mastercard/session/openWallet', $params),
                'session_url' => Mage::getUrl('mastercard/session/createSession', $params),
                'place_order_url' => Mage::getUrl('mastercard/session/updateSessionFromWallet', array('_secure' => true)),
                'client_id' => $this->getConfig()->getClientId(),
                'env' => $this->getConfig()->getEnv(),
            )
        );
    }
}
