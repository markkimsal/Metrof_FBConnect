<?php
// vim: set tabstop=4 
// +===========================================================================+
// | Facebook Connect Magento Helper                                           |
// +===========================================================================+
// | License: Proprietary                                                      |
// +===========================================================================+
// | Copyright (c) 2009 Mark Kimsal                                            |
// | All rights reserved.                                                      |
// |                                                                           |
// | Redistribution and use in source and binary forms, with or without        |
// | modification, are not permitted without express written consent.          |
// |                                                                           |
// |                                                                           |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS       |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT         |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR     |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER|
// | OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,  |
// | EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,       |
// | PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR        |
// | PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF    |
// | LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING      |
// | NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS        |
// | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.              |
// +===========================================================================+

/**
 * Metrof_FBConnect default data helper
 *
 */
class Metrof_FBConnect_Helper_Account extends Mage_Core_Helper_Abstract
{

	/**
	 * Returns true if the user is connected with FB
	 */
	public function userHasFbAccount() {
		$user =  Mage::getSingleton('customer/session')->getCustomer();

		if (is_object($user)) {
			$uid = $user->getId();
		} else {
			$uid = $user;
		}
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$pref = Mage::getConfig()->getTablePrefix();
		$stmt = $read->query('select `fb_uid` from `'.$pref.'fb_uid_link` where user_id = "'.$uid.'"');
		$q = $stmt->fetchAll();
		if (count($q) > 0) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns true if the user is connected with FB
	 */
	public function userHasClaimedAccount() {
		$user =  Mage::getSingleton('customer/session')->getCustomer();
		$uid = $user->getId();

		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$pref = Mage::getConfig()->getTablePrefix();
		$stmt = $read->query('SELECT `claimed_user_id`, `user_id` FROM `'.$pref.'fb_uid_link` WHERE user_id = "'.$uid.'"');
		$q = $stmt->fetchAll();
		if (!isset($q[0])) {
			return FALSE;
		}
		if ($q[0]['claimed_user_id'] == NULL ||
			$q[0]['claimed_user_id'] == 0) {
				return FALSE;
		}
		//negative 1 means they won't ever claim
		//0 or null means no answer yet
		//positive number should match user_id field
		return TRUE;
	}

	/**
	 * Convert the orders in old account to new account
	 *
	 * @return Int  number of orders converted
	 */
	public function convertAccountOrders($fromId, $toId) {
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$pref = Mage::getConfig()->getTablePrefix();
		$stmt = $read->query('SELECT `entity_id` FROM `'.$pref.'sales_order` WHERE customer_id = "'.$fromId.'"');
		$orderIds = array();
		$q = $stmt->fetchAll();

		foreach ($q as $_row) {
			$orderIds[] = $_row['entity_id'];
		}
		if (!count($orderIds)) {
			return 0;
		}

		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$updateStmt = $write->query('UPDATE  `'.$pref.'sales_order` SET `customer_id` = "'.$toId.'" WHERE `entity_id`
			IN ('.implode(',', $orderIds).')');
		$res = $updateStmt->execute();

		return $updateStmt->rowCount();
	}

	public function convertFbUidLink($oldId, $fbUid, $storeId, $claimedId) {
		/*
		var_dump($oldId);
		var_dump($claimedId);
		exit();
		$pref = Mage::getConfig()->getTablePrefix();
		$write = Mage::getSingleton('core/resource')->getConnection('core_read');
		 */

		$fbuid = Mage::getModel('fbconnect/fbuid');
		//$x = Mage::getResourceSingleton('fbconnect/fbuid');
		$fbuid->loadByUserId($oldId);
		$fbuid->setData('store_id', $storeId);
		$fbuid->setData('claimed_user_id', $claimedId);
		$fbuid->setData('updated_at', date('Y-m-d H:i:s'));
		$fbuid->setData('fb_uid', $fbUid);
		$fbuid->setData('user_id', $claimedId);
		$fbuid->save();

		return TRUE;
/*
		if ($claimedId) {
			$stmt = $write->prepare('insert into `'.$pref.'fb_uid_link` (user_id, fb_uid, store_id, created_at, claimed_user_id) VALUES 
			('.$oldId.', '.$fbUid.', '.$storeId.', "'.date('Y-m-d H:i:s').'", '.$claimedId.')');

			$updateStmt = $write->prepare('UPDATE `'.$pref.'fb_uid_link` SET user_id ='.$oldId.', 
				store_id = '.$storeId.', claimed_user_id = '.$claimedId.',
				updated_at = "'.date('Y-m-d H:i:s').'"
				WHERE fb_uid = '.$fbUid.'');
		} else {
			$stmt = $write->prepare('insert into `'.$pref.'fb_uid_link` (user_id, fb_uid, store_id, created_at) VALUES 
			('.$oldId.', '.$fbUid.', '.$storeId.', "'.date('Y-m-d H:i:s').'")');

			$updateStmt = $write->prepare('UPDATE `'.$pref.'fb_uid_link` SET user_id ='.$oldId.', 
				store_id = '.$storeId.',
				updated_at = "'.date('Y-m-d H:i:s').'"
				WHERE fb_uid = '.$fbUid.'');
		}
		//updateStmt->execute() always returns true
		// and the rowCount is 0 if the data doesn't change
		// must check for row existance with a select
		try {
			if ($this->userIsFb($customerId)) {
				$res = $updateStmt->execute();
			} else {
				$res = $stmt->execute();
			}
			return TRUE;
		} catch (Exception $e) {
			$res = FALSE;
		}
		return FALSE;
 */
	}


}

