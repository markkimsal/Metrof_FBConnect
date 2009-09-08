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
ALTER TABLE {$this->getTable('fb_uid_link')}
 ADD COLUMN `claimed_user_id` INTEGER UNSIGNED DEFAULT NULL AFTER `fb_uid`;
");
}
$installer->endSetup();
