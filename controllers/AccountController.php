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
    /**
	 * really complicated way of saying $requireLogin = true;
     *
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

		if (!Mage::getSingleton('customer/session')->authenticate($this)) {
			$this->setFlag('', 'no-dispatch', true);
		}

    }

    public function indexAction() {
		$this->loadLayout();

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

		//make the "facebook connect" menuy item "active"
        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('fbc/account');
        }

		$this->renderLayout();
	}

	/**
	 * Allow a user to claim an existing account with username and password
	 */
	public function claimAction() {
		//if the user username and password are a match

		$sess = Mage::getSingleton('customer/session');

		$oldUser = clone $sess->getCustomer();
		//we want the oldUser completely disassociated from the current user
		// to avoid any potential hacking attempts
		$oldUid = $oldUser->getId();

		$login = $this->getRequest()->getPost('login');
		if (!empty($login['username']) && !empty($login['password'])) {
			try {

				//this will change the old user into the new user with "loadByEmail"
        		if ($oldUser->authenticate($login['username'], $login['password'])) {
					$sess->setCustomer($oldUser);
					$store = Mage::app()->getStore();
					$storeId = $store->getStoreId();
					$fbObj = Mage::helper('fbconnect')->getFb();
					Mage::helper('fbconnect/account')->convertFbUidLink($oldUid, $fbObj->user, $storeId, $oldUser->getId());
					Mage::helper('fbconnect/account')->convertAccountOrders($oldUid, $oldUser->getId());
					//might not need this dispatch stuff, user is already "logged in"
					/*
					Mage::dispatchEvent('customer_login', array('customer'=>$oldUser));
					Mage::dispatchEvent('customer_customer_authenticated', array(
						'model'    => $sess,
						'password' => '',
					));
					 */

					$this->_redirect('fbc/account');
					return;
				} else {
					die('no got login');
				}
			}
			catch (Exception $e) {
				$sess->addError($e->getMessage());
				$sess->setUsername($login['username']);
				$this->_redirect('fbc/account');
				return;
			}

		}

		$sess->addError($this->__('Login and password are required'));
		$this->_redirect('fbc/account');
	}

	/**
	 * Set the referer to claimFbComplete action and redirect to login
	 */
	public function claimFbAction() {
		$session = Mage::getSingleton('customer/session');
		$session->setData('fbc_refer', Mage::getUrl('fbc/account/claimFbComplete'));
		$this->_redirect('fbc/index/login');
	}

	/**
	 * Claimed ID might not have been set properly
	 *
	 * If the user created an account, then logged in with FB *twice*, 
	 * we need to adjust the claimed ID
	 */
	public function claimFbCompleteAction() {
		$sess = Mage::getSingleton('customer/session');
		$newUser = clone $sess->getCustomer();


		//user waa
		$store = Mage::app()->getStore();
		$storeId = $store->getStoreId();
		$fbObj = Mage::helper('fbconnect')->getFb();

		if (!$newUser->getId()) {
			//user is not logged in
			$this->_redirect('fbc/account/');
			return TRUE;
		}
		if (!$fbObj->user) {
			//someone's trying to fake a cookie
			$this->_redirect('fbc/account/');
			return TRUE;
		}
		$fbuid = Mage::getModel('fbconnect/fbuid');
		$fbuid->loadByFbUid($fbObj->user);

		if ($fbuid->getData('claimed_user_id') > 0) {
			//this account has already been claimed
			$this->_redirect('fbc/account/');
			return TRUE;
		}
		$oldUid = $fbuid->getData('user_id');
		$fbuid->setData('store_id', $storeId);
		$fbuid->setData('claimed_user_id', $newUser->getId());
		$fbuid->setData('updated_at', date('Y-m-d H:i:s'));
		$fbuid->setData('user_id', $newUser->getId());
		$fbuid->save();

//		Mage::helper('fbconnect/account')->convertFbUidLink($oldUid, $fbObj->user, $storeId, $oldUser->getId());
		Mage::helper('fbconnect/account')->convertAccountOrders($oldUid, $newUser->getId());

		$this->_redirect('fbc/account/');
		return TRUE;
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
