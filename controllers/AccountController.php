<?

class Metrof_FBConnect_AccountController extends Mage_Core_Controller_Front_Action
{
    public function indexAction() {
		$this->loadLayout();
   //     $this->loadLayoutUpdates();
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
