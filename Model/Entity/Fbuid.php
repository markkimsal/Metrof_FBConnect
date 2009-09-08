<?php

class Metrof_FBConnect_Model_Entity_Fbuid extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('fbconnect/fbuid_link', 'fb_uid_link_id');
    }
}
