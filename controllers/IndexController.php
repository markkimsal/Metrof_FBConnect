<?

class Metrof_FBConnect_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction() {
		$this->loadLayout();
   //     $this->loadLayoutUpdates();
		$this->renderLayout();
	}

	/**
	 * Redirect to facebook with proper API keys in the URL
	 */
	public function loginAction() {
		$urlParams = array();
		$urlParts = array();
		$urlParams['return_session'] = 1;
		$urlParams['fbconnect']      = 1;
		$urlParams['extern']         = 2;
		$urlParams['display']        = 'popup';
		$urlParams['api_key']        = urlencode(Mage::getConfig()->getNode('default/fbconnect/apikey'));
		$urlParams['next']           = urlencode(Mage::getUrl('fbc/index/xdreceiver'));
		$urlParams['cancel_url']     = urlencode(Mage::getUrl('*'));
		$urlParams['fname']          = '_opener';
		$urlParams['locale']         = 'en_US';
		$urlParams['channel_url']    = urlencode(Mage::getUrl('fbc/index/xdreceiver'));
		$url = 'http://www.facebook.com/login.php?';
		foreach ($urlParams as $_k => $_v) {
			$urlParts[] = $_k.'='.$_v;
		}
		$url .= implode('&', $urlParts);
		header('Location: '.$url);
		exit();

//	http://www.facebook.com/login.php?return_session=1&nochrome=1&fbconnect=1&extern=2&display=popup&api_key=1bedcf4953aef1d8e8ce94c7801c8f3f&v=1.0&next=http%3A%2F%2Fhayley.metrofindings.com%2Fcognifty_ws%2Fwww%2Findex.php%2Ffbconnect.main.xdreceiver%2F%3Ffb_login%26fname%3D_opener%26guid%3D0.9244562202757464&cancel_url=http%3A%2F%2Fhayley.metrofindings.com%2Fcognifty_ws%2Fwww%2Findex.php%2Ffbconnect.main.xdreceiver%2F%23fname%3D_opener%26%257B%2522t%2522%253A3%252C%2522h%2522%253A%2522fbCancelLogin%2522%252C%2522sid%2522%253A%25220.389%2522%257D&channel_url=http%3A%2F%2Fhayley.metrofindings.com%2Fcognifty_ws%2Fwww%2Findex.php%2Ffbconnect.main.xdreceiver%2F&locale=en_US
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
		$fbSecret = (string)Mage::getConfig()->getNode('default/fbconnect/secret');
		$fbParams = $this->_getXdParams($fbSecret);

		//find an existing FB UID
		if (!isset($fbParams['user'])) {
			//failure
        	$sess = Mage::getSingleton('customer/session');
			$sess->addWarning('Your facebook login failed.  Try again later.');
        	$this->_redirect('customer/account/login');
			return false;
		}


		Mage::helper('fbconnect')->setFbCookies($fbParams);
		//if found, login user
		$exUid = $this->findExistingUid($fbParams);
		if (!$exUid) {
			//if not found, create a new one
			$user = $this->makeNewUser($fbParams);
		} else {
			$user = Mage::getModel('customer/customer')->load($exUid);
		}
		$sess = Mage::getSingleton('customer/session');
		$sess->setCustomer($user);
		Mage::dispatchEvent('customer_login', array('customer'=>$user));
		Mage::dispatchEvent('customer_customer_authenticated', array(
		   'model'    => $sess,
		   'password' => '',
		));


		$desiredAttr = array('first_name', 'last_name', 'pic_square_with_logo', 'username', 'current_location');
		$attr = Mage::helper('fbconnect')->getDesiredAttr($desiredAttr);

		if ($attr['last_name'] !== '') {
			$user->setLastname($attr['last_name']);
		} else {
			$user->setLastname('Customer');
		}
		if ($attr['first_name'] !== '') {
			$user->setFirstname($attr['first_name']);
		} else {
			$user->setFirstname('Guest');
		}
		$user->save();

		//add or update shipping addresses
		if ($attr['current_location'] != NULL) {
			//setup shipping address.
		}

		//redirect 
        $this->_redirect('customer/account');

       	$sess = Mage::getSingleton('customer/session');
		$sess->addSuccess(
			sprintf('Congratulations.  Your facebook account is now connected with our store.  You can edit your 
			shipping addresses in your account page. <a href="%s">click here to visit your account page.</a>',
				Mage::helper('customer')->getDashboardUrl()
			));
		$sess->addSuccess(
			'<img class="fb_profile_pic_rendered" style="" title="you" alt="you" src="'.$attr['pic_square_with_logo'].'"/>
		Welcome, '.$attr['first_name'].'!
			');
		/*
		 */

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
		$_s = json_decode($ses);
		//fix for incorrect FB API
		$fb_params = array();
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
		$expectedSig = $_s->sig;
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


	protected function makeNewUser($fbParams) {
		$pref = Mage::getConfig()->getTablePrefix();
		$store = Mage::app()->getStore();
		$storeId = $store->getStoreId();
		$webstId = $store->getWebsiteId();
		$customer = Mage::getModel('customer/customer');
		$customer->setData('store_id',   $storeId);
		$customer->setData('website_id', $webstId);
		$customer->setData('is_active', 1);
		$customer->save();
		$customerId = $customer->getId();
//		var_dump($customerId);exit();

		$write = Mage::getSingleton('core/resource')->getConnection('core_read');
		$stmt = $write->prepare('insert into `'.$pref.'fb_uid_link` (user_id, fb_uid, store_id, created_at) VALUES 
		('.$customerId.', '.$fbParams['user'].', '.$storeId.', "'.date('Y-m-d').'")');
		$stmt->execute();

		return $customer;
	}
}
