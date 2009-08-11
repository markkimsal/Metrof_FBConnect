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


	public function getDesiredAttr($attr) {
		$fbObj        = $this->getFb();
		$fbInfos = $fbObj->api_client->users_getInfo($fbObj->user, $attr);
		if (isset($fbInfos[0]))
			return $fbInfos[0];
		$ret = array();
		foreach ($attr as $v) {
			$ret[$v] = null;
		}
		return $ret;
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
	public function makeNewUser($fbParams) {
		$pref = Mage::getConfig()->getTablePrefix();
		$store = Mage::app()->getStore();
		$storeId = $store->getStoreId();
		$webstId = $store->getWebsiteId();
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
		$customer->save();
		$customerId = $customer->getId();

		$write = Mage::getSingleton('core/resource')->getConnection('core_read');
		$stmt = $write->prepare('insert into `'.$pref.'fb_uid_link` (user_id, fb_uid, store_id, created_at) VALUES 
		('.$customerId.', '.$fbParams['user'].', '.$storeId.', "'.date('Y-m-d H:i:s').'")');
		$stmt->execute();

		return $customer;
	}

	public function getApiKey() {
		return (string) Mage::getStoreConfig('metrof_fbc/fbconnect/apikey');
	}
}
