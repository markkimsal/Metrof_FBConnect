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


class Metrof_FBConnect_XdreceiverController extends Mage_Core_Controller_Front_Action
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
	public function indexAction() {
		$t = array();
		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			$t['xdcomm'] = '<script src="https://www.connect.facebook.com/js/api_lib/v0.4/XdCommReceiver.debug.js" type="text/javascript"></script>';
		} else {
			$t['xdcomm'] = '<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/XdCommReceiver.debug.js" type="text/javascript"></script>';
		}

		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
		echo "\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml" > <body>' ."\n";

		foreach ($t as $_t) {
			echo $_t;
		}
		echo' </body> </html>';

exit();
	}

}


