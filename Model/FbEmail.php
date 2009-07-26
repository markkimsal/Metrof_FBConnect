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
			$fbUid = $this->_findFbUserByContext();
			if (!$fbUid) return false;
		}

		if (isset($fbUid)) {
			$variables['email'] = $email;
			$variables['name'] = $name;
	        try {
				$this->_sendAsFbNotification($variables);
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
		if (stripos($req->getModuleName(), 'admin')) {
			$isAdmin = true;
		} else {
			$isAdmin = false;
		}

		if (!$isAdmin) {
			$fbObj = Mage::helper('fbconnect')->getFb();
			return $fbObj->user;
		}
		return 0;
	}

	public function _sendAsFbNotification($variables) {
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
  		$ret  = $fbObj->api_client->notifications_sendEmail($fbObj->user, $subject, $text, $fbml);
  		$ret2 = $fbObj->api_client->notifications_send(array($fbObj->user), $subject. '.  <a href="'.Mage::getUrl('customer/account').'">Visit your account.</a>', 'app_to_user');
		return $ret;
	}
}
