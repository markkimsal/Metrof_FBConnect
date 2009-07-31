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


/**
 * This class allows a customer's wishlist to be pushed to Facebook.
 */
class Metrof_FBConnect_AccountController extends Mage_Core_Controller_Front_Action
{
    public function indexAction() {
		$this->loadLayout();
		$this->renderLayout();
	}

    public function wishlistNotifyAction() {

		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$pref = Mage::getConfig()->getTablePrefix();


		$fbObj        = Mage::helper('fbconnect')->getFb();

		if (!$fbObj->user) {
			echo "you are not logged in.";
		}
		$uid = $fbObj->user;
//  		$ret = $fbObj->api_client->notifications_send(array($uid), "This is a test notification", 'app_to_user');

		$ret = $fbObj->api_client->users_hasAppPermission('email', $uid);
  		//$ret = $fbObj->api_client->notifications_sendEmail($uid, "Your order has been shipped", "This is a test notification", 'This is a test notification');

		var_dump($ret);

		$this->loadLayout();
		$this->renderLayout();
	}
}
