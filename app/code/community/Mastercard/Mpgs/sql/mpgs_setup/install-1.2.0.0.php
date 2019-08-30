<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

/** @var Mage_Sales_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$installer->addAttribute('quote_payment', 'mpgs_session_id', array());

$installer->endSetup();
