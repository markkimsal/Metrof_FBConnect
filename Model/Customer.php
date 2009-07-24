<?php

class Metrof_FBConnect_Model_Customer
extends Mage_Customer_Model_Customer {

    function _construct()
    {
        $this->_init('fbconnect/customer');
    }

}
