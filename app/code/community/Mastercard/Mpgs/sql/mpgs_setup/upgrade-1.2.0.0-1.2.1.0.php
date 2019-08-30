<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

/** @var Mage_Eav_Model_Entity_Setup $installer */
$installer = $this;

$installer->startSetup();

$entityType = $installer->getEntityTypeId('customer');
$installer->addAttribute(
    $entityType,
    'mpgs_card_token',
    array(
        'type' => 'text',
        'label' => 'MPGS Card Token',
        'input' => 'text',
        'visible' => false,
        'required' => false,
        'default_value' => '',
    )
);

$installer->addAttribute('quote_payment', 'mpgs_save_card', array());

$installer->endSetup();
