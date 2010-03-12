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
class Metrof_FBConnect_Helper_Data extends Mage_Core_Helper_Abstract
{

	public function getLoginUrl() {
        return Mage::getUrl('fbc/index/login');
	}

	public function getClaimFbUrl() {
        return Mage::getUrl('fbc/account/claimFb');
	}

	public function getAddressEditUrl() {
        return Mage::getUrl('customer/address/edit');
	}

	public function getEmailEditUrl() {
        return Mage::getUrl('customer/account/edit');
	}



	public function setFbCookies($fbParams) {
		$fbObj        = $this->getFb($fbParams);
		//HACK to fix Facebook's strange xdreceive handling of 
		//logins and cookies
		$fbObj->api_client->session_key = $fbParams['session_key'];
		$fbObj->set_cookies($fbParams['user'],
			$fbParams['session_key'],
			$fbParams['expires'],
			$fbParams['ss']);
	}

	/**
	 * Get the facebook client object for easy access.
	 */
	public function getFb($fbParams = null) {
		static $facebook = null;

		if ($facebook === null) {
			$apikey   = (string) Mage::getStoreConfig('metrof_fbc/fbconnect/apikey');
			$fbSecret = (string) Mage::getStoreConfig('metrof_fbc/fbconnect/secret');
			$facebook = new Metrof_FBConnect_Helper_Facebook($apikey, $fbSecret);
			if (!$facebook) {
				Mage::throwException('Could not create facebook client.');
			}
			if (is_array($fbParams)) {
				$facebook->fb_params = $fbParams;
				$facebook->fb_params = $fbParams;
				$facebook->api_client->set_user($fbParams['user']);
				$facebook->api_client->session_key = $fbParams['session_key'];
			}
		}
		return $facebook;
	}

	/**
	 * this version of getDesiredAttr makes the API call without the 
	 * users session.  It should not fail as much as the other version
	 * but you can't get the username and current_location w/o the 
	 * user's session/connection
	 *
	 * Facebook's documentation is incorrect, the session ID is *not* optional
	 */

	/*
	public function getDesiredAttr($attr, $fbuid=NULL) {
		$fbObj        = $this->getFbApi();

		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			$fbObj->set_use_ssl_resources(TRUE);
		}
		$fbInfos = $fbObj->users_getInfo($fbuid, $attr);
		//FIX BUG in facebook API which won't return pic_square_with_logo for SSL
		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			if (isset($fbInfos[0])) {
				if (isset($fbInfos[0]['pic_square_with_logo'])
					&& isset($fbInfos[0]['pic_square'])) {
					$fbInfos[0]['pic_square_with_logo'] =
					$fbInfos[0]['pic_square'] ;
				}
			}
		}

		if (isset($fbInfos[0]))
			return $fbInfos[0];

		$ret = array();
		foreach ($attr as $v) {
			$ret[$v] = null;
		}
		return $ret;
	}
	// */

	/**
	 * This verison of getDesiredAttr requires the fbObj
	 * to have an active session on it.  It should be allowed to 
	 * get more attributes because the user is currently logged in.
	 *
	 * Facebook's documentation is incorrect, the session ID is *not* optional
	 */
	// *
	public function getDesiredAttr($attr, $fbuid=NULL) {
		$fbObj  = $this->getFb();

		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			$fbObj->api_client->set_use_ssl_resources(TRUE); 
		}
		$fbInfos = $fbObj->api_client->users_getInfo($fbObj->user, $attr);
		//FIX BUG in facebook API which won't return pic_square_with_logo for SSL
		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			if (isset($fbInfos[0])) {
				if (isset($fbInfos[0]['pic_square_with_logo']) 
					&& isset($fbInfos[0]['pic_square'])) {
					$fbInfos[0]['pic_square_with_logo'] =
					$fbInfos[0]['pic_square'] ;
				}
			}
		}

		if (isset($fbInfos[0]))
			return $fbInfos[0];

		$ret = array();
		foreach ($attr as $v) {
			$ret[$v] = null;
		}
		return $ret;
	}
	// */


	/**
	 * return true or false depending on if the user allows email
	 */
	public function userAllowsEmail($fbUid=NULL) {
		$fbObj        = $this->getFb();
		if ($fbUid === NULL) {
			$fbUid = $fbObj->user;
		}
		$fbInfos = $fbObj->api_client->users_hasAppPermission('email', $fbUid);
		$allow = (int)$fbInfos;

		if (intval($fbUid) < 1) {
			//some problem with fbUid, don't try to save it
			return (bool)$allow;
		}
		//save setting
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$tablePrefix = (string)Mage::getConfig()->getTablePrefix();
		try {
			$write->query( 'UPDATE '.$tablePrefix.'fb_uid_link SET  `allow_email` = ? WHERE fb_uid = ?', array($allow, $fbUid));
		} catch (Exception $e) {
			return FALSE;
		}
		return (bool)$allow;
	}


	/**
	 * Returns true if the user is connected with FB
	 */
	public function userIsFb($user) {
		if (is_object($user)) {
			$uid = $user->getId();
		} else{
			$uid = $user;
		}
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$pref = Mage::getConfig()->getTablePrefix();
		$stmt = $read->query('select `fb_uid` from `'.$pref.'fb_uid_link` where user_id = "'.$uid.'"');
		$q = $stmt->fetchAll();
		if (count($q) > 0) {
			return $q[0]['fb_uid'];
		}
		return 0;
	}


	/**
	 * Make a new user out of some parameters
	 * @return Object $customer new customer
	 */
	public function makeNewUser($fbParams, $currentUser) {
		$store = Mage::app()->getStore();
		$storeId = $store->getStoreId();
		$webstId = $store->getWebsiteId();
		$claimedId = NULL;
		if ($currentUser->getId()) {
			//don't do anything, the user has an account
			$claimedId = $currentUser->getId();
			$customerId = $currentUser->getId();
			$customer = $currentUser;
		} else {
			//magento sets the ID to null in the controller post action, so we do the same
			$customer = Mage::getModel('customer/customer')->setId(null);
			$customer->setData('store_id',   $storeId);
			$customer->setData('website_id', $webstId);
			$customer->setData('is_active', 1);
			$customer->setData('created_at', date('Y-m-d H:i:s'));
			$customer->setData('updated_at', date('Y-m-d H:i:s'));
			//this will set the group id
			$customer->getGroupId();
			//this wills et the tax class id
			$customer->getTaxClassId();

			//force new customers active since FB users cannot 
			// reply to emails
			$customer->setForceConfirmed(true);
			$customer->save();
			$customerId = $customer->getId();
		}

		$this->createNewFbUidLink($customerId, $fbParams['user'], $storeId, $claimedId);
		return $customer;
	}

	public function createNewFbUidLink($customerId, $fbUid, $storeId, $claimedId) {
		$pref = Mage::getConfig()->getTablePrefix();
		$write = Mage::getSingleton('core/resource')->getConnection('core_read');
		if ($claimedId) {
			$stmt = $write->prepare('insert into `'.$pref.'fb_uid_link` (user_id, fb_uid, store_id, created_at, claimed_user_id) VALUES 
			('.$customerId.', '.$fbUid.', '.$storeId.', "'.date('Y-m-d H:i:s').'", '.$claimedId.')');

			$updateStmt = $write->prepare('UPDATE `'.$pref.'fb_uid_link` SET user_id ='.$customerId.', 
				store_id = '.$storeId.', claimed_user_id = '.$claimedId.',
				updated_at = "'.date('Y-m-d H:i:s').'"
				WHERE fb_uid = '.$fbUid.'');
		} else {
			$stmt = $write->prepare('insert into `'.$pref.'fb_uid_link` (user_id, fb_uid, store_id, created_at) VALUES 
			('.$customerId.', '.$fbUid.', '.$storeId.', "'.date('Y-m-d H:i:s').'")');

			$updateStmt = $write->prepare('UPDATE `'.$pref.'fb_uid_link` SET user_id ='.$customerId.', 
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
	}



	public function getApiKey() {
		return (string) Mage::getStoreConfig('metrof_fbc/fbconnect/apikey');
	}

	/**
	 * Deletes and fb_uid_link row given the *$userID*, not the fb_uid
	 */
	public function deleteFbUidByUserId($userId) {
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');

		$tablePrefix = (string)Mage::getConfig()->getTablePrefix();
		try {
			$write->query( 'DELETE FROM '.$tablePrefix.'fb_uid_link WHERE `user_id` = ?', $userId);
		} catch (Exception $e) {
			//this is a very rare situation in which we need to delete an fb uid.
			//the possibility of it failing and us knowing what to do is extremely small
		}
	}

	public function getFbApi() {

		$apikey   = (string) Mage::getStoreConfig('metrof_fbc/fbconnect/apikey');
		$fbSecret = (string) Mage::getStoreConfig('metrof_fbc/fbconnect/secret');
		//$dummy is used just to load the parent class
		$dummy = new Metrof_FBConnect_Helper_Facebook($apikey, $fbSecret);
		return new Metrof_FBConnect_Helper_FacebookRestClient($apikey, $fbSecret, null);
	}

	/**
	 * Return the img src attribute of the facebook connect button.
	 *
	 * Respects SSL/non-SSL modes
	 */
	public function getFbButtonSrc($size='medium', $len='long', $color='light') {
		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			return 'https://www.facebook.com/images/fbconnect/login-buttons/connect_'.$color.'_'.$size.'_'.$len.'.gif';
		} else {
			return 'http://static.ak.fbcdn.net/images/fbconnect/login-buttons/connect_'.$color.'_'.$size.'_'.$len.'.gif';
		}

	}

	public function getEmailPermHref() {
		$apikey   = (string) Mage::getStoreConfig('metrof_fbc/fbconnect/apikey');
		return 'http://www.facebook.com/authorize.php?api_key='.$apikey.'&v=1.0&ext_perm=email';
	}

	public function getFeatureLoaderSrc() {
		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			return 'https://www.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php';
		} else {
			return 'http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php';
		}
	}

	public function getXdReceiverUrl() {
		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			return Mage::getUrl('fbc/xdreceiver/index', array('_forced_secure'=>true));
		} else {
			return Mage::getUrl('fbc/xdreceiver/index');
		}
	}

	/**
	 * Double check if session is still active, if not, the 
	 * user should be logged out with $this->killFbSession($fbObj);
	 */
	public function isSessionGolden($fbObj) {
		try {
			if (!$fbObj->get_loggedin_user()) {
				return false;
			}
			$user = $fbObj->api_client->fql_query('SELECT uid, pic_square, first_name FROM user WHERE uid = ' . $fbObj->get_loggedin_user());
		} catch (Exception $e) {
			return false;
		}
		if (is_array($user) && isset($user[0])) {
			return true;
		}
		return false;
	}

	public function killFbSession($fbObj) {
		$fbObj->set_user(null, null);	
		$fbObj->clear_cookie_state();
	}
}
