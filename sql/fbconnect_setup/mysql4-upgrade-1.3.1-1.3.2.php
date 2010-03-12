<?php

/**
 * FBConnect installer
 *
 * @category   Metrof
 * @package    Metrof_FBConnect
 */
$installer = $this;

$installer->startSetup();

if ($installer->tableExists($installer->getTable('fb_uid_link'))) {

$installer->run("
ALTER TABLE {$this->getTable('fb_uid_link')}
 CHANGE COLUMN `fb_uid` `fb_uid` BIGINT UNSIGNED NULL DEFAULT NULL;
");
}
$installer->endSetup();
