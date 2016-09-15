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
 * @author Alistair Stead
 * @version $Id$
 * @copyright Mastercard, 11 April, 2011
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @package Mastercard
 **/

/**
 * Mastercard_Mpgs_Model_Source_Endpoints
 *
 * This class adapts the authentication types list in the configuration to an OptionArray
 *
 * @package Mastercard
 * @subpackage Helper
 * @author Alistair Stead
 */
class Mastercard_Mpgs_Model_Source_Authtypes {

	/**
	 * Return a list of available authentication types
	 *
	 * @return array
	 * @author Alistair Stead
	 *
	 */
	public function toOptionArray() {

		foreach ( Mage::getSingleton( 'mpgs/config' )->getTransactionTypes() as $code => $name ) {
			$options [] = array (
					'value' => $code,
					'label' => $name
			);
		}

		return $options;

	}
}
