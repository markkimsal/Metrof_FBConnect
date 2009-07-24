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
	 */
	public function xdreceiverAction() {
		//hash URL params
		$fbSecret = Mage::getConfig()->getNode('default/fbconnect/secret');
		$fbParams = $this->_getXdParams($fbSecret);

		//find an existing FB UID
		if (isset($fbParams['user']))
			echo "you did it";
		else 
			echo "login problem";


		//if found, login user

		//if not found, create a new one

		//add or update shipping addresses

		//redirect 
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
}
