CREATE TABLE `fb_uid_link` (
	  `fb_uid_link_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	  `fb_uid` INT UNSIGNED NOT NULL DEFAULT 0,
	  `cgn_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
	  PRIMARY KEY(`fb_uid_link_id`)
);

ALTER TABLE `fb_uid_link` ADD INDEX `fb_uid_idx`( `fb_uid`);
ALTER TABLE `fb_uid_link` ADD INDEX `cgn_user_idx`(`cgn_user_id`);
