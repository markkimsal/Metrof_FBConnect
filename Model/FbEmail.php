<?php

class Metrof_FBConnect_Model_FbEmail 
extends Mage_Core_Model_Email_Template {

    /**
     * Return true if this template can be used for sending queue as main template
     *
     * @return boolean
     */
    public function isValidForSend()
    {
        return !Mage::getStoreConfigFlag('system/smtp/disable')
            && $this->getSenderName()
            && $this->getSenderEmail()
            && $this->getTemplateSubject();
    }

	/**
     * Send mail to recipient
     *
     * @param   string      $email		  E-mail
     * @param   string|null $name         receiver name
     * @param   array       $variables    template variables
     * @return  boolean
     **/
    public function send($email, $name=null, array $variables = array())
    {
        if(!$this->isValidForSend()) {
            return false;
        }

		if ($email == '') {
			$fbContext = $this->_findFbUserByContext();
			if (!$fbContext) return false;
		}

		if (isset($fbContext)) {
			$variables['email'] = $email;
			$variables['name'] = $name;
	        try {
				$fbUid   = $fbContext['fbUid'];
				$storeId = $fbContext['storeId'];
				$this->_sendAsFbNotification($variables, $fbUid, $storeId);
			}
			catch (Exception $e) {
				return false;
			}
			return true;
		}

        if (is_null($name)) {
            $name = substr($email, 0, strpos($email, '@'));
        }

        $variables['email'] = $email;
        $variables['name'] = $name;

        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

        $mail = $this->getMail();
        if (is_array($email)) {
            foreach ($email as $emailOne) {
                $mail->addTo($emailOne, $name);
            }
        } else {
            $mail->addTo($email, '=?utf-8?B?'.base64_encode($name).'?=');
        }

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);

        if($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }


        $mail->setSubject('=?utf-8?B?'.base64_encode($this->getProcessedTemplateSubject($variables)).'?=');
        $mail->setFrom($this->getSenderEmail(), $this->getSenderName());

        try {
            $mail->send(); // Zend_Mail warning..
            $this->_mail = null;
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }

	/**
	 * Find an FBUID based on the current module
	 * if front end, use cookies
	 * if backend, use order
	 */
	public function _findFbUserByContext() {
		$req = Mage::app()->getRequest();
		$ret = array(
			'mode' => '',
			'storeId'    => NULL,
			'fbUid'      => NULL,
			'customerId' => NULL);


		if (stripos($req->getModuleName(), 'admin') !== FALSE) {
			$isAdmin = true;
		} else {
			$isAdmin = false;
		}

		if (!$isAdmin) {
			$fbObj = Mage::helper('fbconnect')->getFb();
			return $fbObj->user;
		}
		if ($isAdmin) {
			if ($order = Mage::registry('current_order')) {
				$ret['mode'] = 'current_order';
				$customerId = $order->getCustomerId();

				if (intval($customerId) > 0) {
					$ret['customerId'] = intval($customerId);
					$ret['fbUid']      =  Mage::helper('fbconnect')->userIsFb(intval($customerId));
					$ret['storeId']    = (int)$order->getData('store_id');
					return $ret;
				}
				//no customer associated
				return NULL;
			}
			if ($invoice = Mage::registry('current_invoice')) {
				$ret['mode'] = 'current_invoice';
				$customerId = $invoice->getCustomerId();
				if (intval($customerId) > 0) {
					$ret['customerId'] = intval($customerId);
					$ret['fbUid']      =  Mage::helper('fbconnect')->userIsFb(intval($customerId));
					$ret['storeId']    = (int)$invoice->getData('store_id');
					return $ret;
				}
				//no customer associated
				return NULL;
			}
			if ($shipment = Mage::registry('current_shipment')) {
				$ret['mode'] = 'current_shipment';
				$customerId = $shipment->getCustomerId();
				if (intval($customerId) > 0) {
					$ret['customerId'] = intval($customerId);
					$ret['fbUid']      =  Mage::helper('fbconnect')->userIsFb(intval($customerId));
					$ret['storeId']    = (int)$shipment->getData('store_id');
					return $ret;
				}
				//no customer associated
				return NULL;
			}



			return NULL;
		}
		return 0;
	}

	/**
	 * Send a small notification to the customer and a full e-mail if they allow it.
	 *
	 * @param Array $variables same variables sent to regular email_template
	 * @param int   $fbUid  if null, the currently logged in user is assumed to the recipient (front-end okay)
	 * @param int   $storeId  if null, the currently active store is assumed to be the default URL for links (front-end okay)
	 */
	public function _sendAsFbNotification($variables, $fbUid = NULL, $storeId = NULL) {
        $text = $this->getProcessedTemplate($variables, true);
        $subject = $this->getProcessedTemplateSubject($variables);

        if($this->isPlain()) {
			$fbml = '';
        } else {
//            $mail->setBodyHTML($text);
			$fbml = $text;
			$text = strip_tags($fbml);
        }
		$fbObj = Mage::helper('fbconnect')->getFb();
		if ($fbUid === NULL) {
			//use the currently logged in user
			$fbUid = $fbObj->user;
		}

		/*
		if ($storeId === NULL) {
			$storeId = Mage::app()->getStore()->getId();
		}
		 */
		$url = Mage::app()->getStore($storeId)->getUrl('customer/account');

  		$ret  = $fbObj->api_client->notifications_sendEmail($fbUid, $subject, $text, $fbml);
  		$ret2 = $fbObj->api_client->notifications_send(array($fbUid), $subject. '.  <a target="_blank" href="'.$url.'">Visit your account.</a>', 'app_to_user');
		return $ret;
	}
}
