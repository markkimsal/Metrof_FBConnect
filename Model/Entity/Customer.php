<?php

/**
 * Need to override standard customer to avoid bad email checking
 */
class Metrof_FBConnect_Model_Entity_Customer 
extends Mage_Customer_Model_Entity_Customer  {

    /**
     * Check customer scope, email and confirmation key before saving
     *
     * @param Varien_Object $customer
     * @return Mage_Customer_Model_Entity_Customer
     * @throws Mage_Core_Exception
     */
    protected function _beforeSave(Varien_Object $customer)
    {

		//old customer, do old checking
		if ($customer->getId()) {
			$select = $this->_getReadAdapter()->select()
				->from($this->getEntityTable(), array($this->getEntityIdField()))
				;
	//FB doesn't always give us an email... get over it.
	//            ->where('email=?', $customer->getEmail());
			if ($customer->getSharingConfig()->isWebsiteScope()) {
				$select->where('website_id=?', (int) $customer->getWebsiteId());
			}
			if ($customer->getId()) {
				$select->where('entity_id !=?', $customer->getId());
			}

			if ($this->_getWriteAdapter()->fetchOne($select)) {
				Mage::throwException(Mage::helper('customer')->__('Customer email already exists'));
			}
		}

        // set confirmation key logic
        if ($customer->getForceConfirmed()) {
            $customer->setConfirmation(null);
        }
        elseif ((!$customer->getId()) && ($customer->isConfirmationRequired())) {
            $customer->setConfirmation($customer->getRandomConfirmationKey());
        }
        // remove customer confirmation key from database, if empty
        if (!$customer->getConfirmation()) {
            $customer->setConfirmation(null);
        }

        return $this;
    }
}
