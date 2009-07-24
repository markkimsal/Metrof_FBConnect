<?php

/**
 * FBConnect installer
 *
 * @category   Metrof
 * @package    Metrof_FBConnect
 */
$installer = $this;

$installer->startSetup();

if (!$installer->tableExists($installer->getTable('fb_uid_link'))) {

$installer->run("
-- DROP TABLE IF EXISTS {$this->getTable('fb_uid_link')};
CREATE TABLE {$this->getTable('fb_uid_link')} (
  `fb_uid_link_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default 0,
  `fb_uid` int(10) unsigned NOT NULL default 0,
  `store_id` smallint(5) unsigned NOT NULL default '0',
  `created_at` datetime NOT NULL default '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL default '0000-00-00 00:00:00',
  `is_active` tinyint(1) unsigned NOT NULL default '1',
  PRIMARY KEY  (`fb_uid_link_id`)
);
");
 
}
$installer->endSetup();



/*
$read = Mage::getSingleton('core/resource')->getConnection('core_read');
$read->query('select `entity_id` from `catalog_product_entity');

$select = $read->select();
$select->from('eav_entity_type', 'entity_type_id');
$dumb = 'catalog_product';
$select->where('entity_type_code = "quote_item"');
$stmt = $select->query();
$result = $stmt->fetchAll();
$quote_type_id = $result[0]['entity_type_id'];


$select = $read->select();
$select->from('eav_entity_type', 'entity_type_id');
$dumb = 'catalog_product';
$select->where('entity_type_code = "order_item"');
$stmt = $select->query();
$result = $stmt->fetchAll();
$order_type_id = $result[0]['entity_type_id'];
 */
