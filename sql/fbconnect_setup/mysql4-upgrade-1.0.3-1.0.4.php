<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$installer->startSetup();

$installer->run("
	update ".$this->getTable('customer_entity')." c
	LEFT JOIN ".$this->getTable('fb_uid_link')." fb ON c.entity_id = fb.user_id
	set c.created_at =  fb.created_at,
	    c.updated_at =  fb.created_at
		WHERE fb.user_id IS NOT NULL
");

$installer->run("
	ALTER TABLE ".$this->getTable('fb_uid_link')." 
    ADD COLUMN `allow_email` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `is_active`
");


$installer->endSetup();
