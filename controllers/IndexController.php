<?
// vim: set tabstop=4 
// +===========================================================================+
// | Facebook Connect Magento Controller                                       |
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


class Metrof_FBConnect_IndexController extends Mage_Core_Controller_Front_Action
{
	public function debugAction() {

		try {
			$desiredAttr = array('first_name', 'last_name', 'pic_square_with_logo', 'username', 'current_location', 'pic_square');
			$attr = Mage::helper('fbconnect')->getDesiredAttr($desiredAttr);
		} catch (FacebookRestClientException $fbe) {
			//session key is probably not valid
			$attr = array();
			//unset cookies
			$fbCookie = array();
			$fbCookie['session_key'] = '';
			$fbCookie['user']        = '';
			$fbCookie['expires']     = '';
			$fbCookie['ss']          = '';
			Mage::helper('fbconnect')->setFbCookies($fbCookie);
		}


		/*
			$sess = Mage::getSingleton('customer/session');
			$sess->addSuccess(
				'<img class="fb_profile_pic_rendered" style="" title="you" alt="you" src="'.$attr['pic_square_with_logo'].'"/> '
			);
		 */
		$this->loadLayout();
		$this->renderLayout();

		//echo				'<img class="fb_profile_pic_rendered" style="" title="you" alt="you" src="'.$attr['pic_square'].'"/> ';
		//echo				'<img class="fb_profile_pic_rendered" style="" title="you" alt="you" src="'.$attr['pic_square_with_logo'].'"/> ';
		//        echo $this->getLayout()->getMessagesBlock()->getGroupedHtml;
		//		echo $this->getMessagesBlock()->getGroupedHtml();
	}

	/**
	 * Redirect to facebook with proper API keys in the URL
	 */
	public function loginAction() {
		//figure out where the user came from
		$session = Mage::getSingleton('customer/session');
		if (!$session->getData('fbc_refer') && isset($_SERVER['HTTP_REFERER'])) {
			$session->setData('fbc_refer', $_SERVER['HTTP_REFERER']);
		}
		//get SSL aware attributes
		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			$forceSecure = TRUE;
		} else {
			$forceSecure = FALSE;
		}
		$locale = Mage::getStoreConfig('general/locale/code');
		$urlParams = array();
		$urlParts = array();
		$urlParams['return_session'] = 1;
		$urlParams['fbconnect']      = 1;
		$urlParams['extern']         = 2;
		$urlParams['display']        = 'popup';
		$urlParams['api_key']        = urlencode(Mage::getStoreConfig('metrof_fbc/fbconnect/apikey'));
		$urlParams['next']           = urlencode(Mage::getUrl('fbc/index/xdreceiver', array('_forced_secure'=>$forceSecure)));
		$urlParams['cancel_url']     = urlencode(Mage::getUrl('*'));
		$urlParams['fname']          = '_opener';
		$urlParams['locale']         = $locale;
		$urlParams['channel_url']    = urlencode(Mage::getUrl('fbc/index/xdreceiver', array('_forced_secure'=>$forceSecure)));
		$url = 'http://www.facebook.com/login.php?';

		foreach ($urlParams as $_k => $_v) {
			$urlParts[] = $_k.'='.$_v;
		}
		$url .= implode('&', $urlParts);
		header('Location: '.$url);
		exit();

	}

	/**
	 * XDReceiver page is unnecessary because FBConnect javascript doesn't work with prototype.
	 *
	 * Steps:
	 *
	 *   hash URL params
	 *   find an existing FB UID
	 *   if found, login user
	 *   if not found, create a new one
	 *   add or update shipping addresses
	 *   redirect 
	 */
	public function xdreceiverAction() {
		//hash URL params
		$fbSecret = (string)Mage::getStoreConfig('metrof_fbc/fbconnect/secret');
		$fbParams = $this->_getXdParams($fbSecret);

		//find an existing FB UID
		if (!isset($fbParams['user'])) {
			//failure
			$sess = Mage::getSingleton('customer/session');
			$sess->addWarning('Your facebook login failed.  Try again later.');
			$this->_redirect('customer/account/login');
			return false;
		}

		$currentUser =  Mage::getSingleton('customer/session')->getCustomer();

		Mage::helper('fbconnect')->setFbCookies($fbParams);
		//if found, login user
		$exUid = $this->findExistingUid($fbParams);
		if (!$exUid) {
			//if not found, create a new one
			$user = Mage::helper('fbconnect')->makeNewUser($fbParams, $currentUser);
		} else {
			$user = Mage::getModel('customer/customer')->load($exUid);
			if ($user->getEntityId() == NULL) {
				//a very rare situation happened,
				// this user was previously deleted but their FB UID link was not.
				// so, we need to delete the FB UID and make a new user
				Mage::helper('fbconnect')->deleteFbUidByUserId($exUid);
				$user = Mage::helper('fbconnect')->makeNewUser($fbParams, $currentUser);
				//set thsi flag for the rest of the script
				$exUid = -1;
			}
		}
		$sess = Mage::getSingleton('customer/session');
		$sess->setCustomer($user);
		Mage::dispatchEvent('customer_login', array('customer'=>$user));
		Mage::dispatchEvent('customer_customer_authenticated', array(
			'model'    => $sess,
			'password' => '',
		));


		try {
			$desiredAttr = array('first_name', 'last_name', 'pic_square_with_logo', 'username', 'current_location', 'pic_square');
			$attr = Mage::helper('fbconnect')->getDesiredAttr($desiredAttr);
		} catch (FacebookRestClientException $fbe) {
			//session key is probably not valid
			$attr = array();
			//unset cookies
			$fbCookie = array();
			$fbCookie['session_key'] = '';
			$fbCookie['user']        = '';
			$fbCookie['expires']     = '';
			$fbCookie['ss']          = '';
			Mage::helper('fbconnect')->setFbCookies($fbCookie);
		}

		$allowsEmail = FALSE;
		//check to see if they allow email messages
		try {
			$allowsEmail = Mage::helper('fbconnect')->userAllowsEmail();
		} catch (FacebookRestClientException $fbe) {
			//already set the cookie to blank above.
			$allowsEmail = FALSE;
		}

		if ($attr['last_name'] != '') {
			$user->setLastname($attr['last_name']);
		} else {
			$user->setLastname('Customer');
		}
		if ($attr['first_name'] != '') {
			$user->setFirstname($attr['first_name']);
		} else {
			$user->setFirstname('Facebook');
		}
		$user->save();

		//add or update shipping addresses
		if ($attr['current_location'] != NULL) {
			//setup shipping address.
		}

		$this->_redirect('customer/account');
		//redirect to where they came from, or customer account page
		$sess = Mage::getSingleton('customer/session');
		$refer = $sess->getData('fbc_refer');
		if ($refer != '') {
			$this->_redirectUrl($refer);
			$sess->setData('fbc_refer', null);
		}

		$apikey = Mage::getStoreConfig('metrof_fbc/fbconnect/apikey');

		if (!$exUid) {
			$sess->addSuccess(
				$this->__('Congratulations.  Your facebook account is now connected with our store.')
			);
			$sess->addSuccess(
				$this->__('You can edit your shipping addresses in your account page.')
			);
			$sess->addSuccess(
				$this->__('Edit your shipping address: <a href="%s">click here.</a>',
					Mage::helper('fbconnect')->getAddressEditUrl()
				)
			);
			$sess->addSuccess(
				$this->__('Add your e-mail: <a href="%s">click here.</a>',
				Mage::helper('fbconnect')->getEmailEditUrl()
			)
		);
			if (!$allowsEmail) {
				$sess->addSuccess(
					$this->__('Or allow us to e-mail you via Facebook: <a target="_blank" href="%s">click here.</a>',
					'http://www.facebook.com/authorize.php?api_key='.$apikey.'&v=1.0&ext_perm=email'
				)
			);
			}

			$sess->addSuccess(
				'<img class="fb_profile_pic_rendered" style="" title="you" alt="you" src="'.$attr['pic_square_with_logo'].'"/> '
			);

			$sess->addSuccess(
				$this->__('Welcome, %s!',
				$user->getFirstname()
			)
		);
		} else {
			$sess->addSuccess(
				'<img class="fb_profile_pic_rendered" style="" title="you" alt="you" src="'.$attr['pic_square_with_logo'].'"/>'
			);

			$sess->addSuccess(
				$this->__('Welcome back, %s!',
				$user->getFirstname()
			)
		);

			if (!$allowsEmail) {
				$sess->addSuccess('<br/>'. 
					$this->__('Allow us to e-mail you via Facebook: <a target="_blank" href="%s">click here.</a>',
						'http://www.facebook.com/authorize.php?api_key='.$apikey.'&v=1.0&ext_perm=email'
					)
				);
			}
		}
	}

	/**
	 * Fix up the passed in GET params to act like
	 * regular cookie params
	 *
	 * @requires access to  Request object
	 */
	protected function _getXdParams($secret) {
		$req = Mage::app()->getRequest();
		$ses = $req->getParam('session');
		$_s = json_decode($ses, true);
		//fix for incorrect FB API
		$fb_params = array();
		if (!is_array($_s)) return $fb_params;
		$str = '';
		foreach ($_s as $_k => $_v) {
			if ($_k == 'sig') continue;
			if ($_k == 'base_domain') continue;
			if ($_k == 'secret'){ 
				$fb_params['ss'] = $_v;
				continue;
			}
			if ($_k == 'uid'){ 
				$fb_params['user'] = $_v;
				continue;
			}
			$fb_params[$_k] = $_v;
		}

		//verify params
		$sig = self::generate_sig($fb_params, $secret);
		$expectedSig = $_s['sig'];
		if ($sig !== $expectedSig) {
			return array();
		}
		return $fb_params;
	}

	public static function generate_sig($params_array, $secret) {
		$str = '';

		ksort($params_array);
		// Note: make sure that the signature parameter is not already included in
		//       $params_array.
		foreach ($params_array as $k=>$v) {
			$str .= "$k=$v";
		}
		$str .= $secret;

		return md5($str);
	}

	/**
	 * Always get back an int, if 0 then nothing found.
	 */
	protected function findExistingUid($fbParams) {
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$pref = Mage::getConfig()->getTablePrefix();
		$stmt = $read->query('select `user_id` from `'.$pref.'fb_uid_link` where fb_uid = "'.$fbParams['user'].'"');
		$q = $stmt->fetchAll();
		if (count($q) > 0) {
			return $q[0]['user_id'];
		}
		return 0;

		$select = $read->select();
		$select->from('eav_entity_type', 'entity_type_id');
		$select->where('entity_type_code = "quote_item"');
		$stmt = $select->query();
		$result = $stmt->fetchAll();
		$quote_type_id = $result[0]['entity_type_id'];
	}
}
