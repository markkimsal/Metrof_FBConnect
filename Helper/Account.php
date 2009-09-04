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
}

