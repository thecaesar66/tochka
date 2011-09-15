<?php
class Iscander_Payway_Block_Form extends Mage_Payment_Block_Form
{
	protected function _construct()
    {
        $this->setTemplate('webpay/form.phtml');
        parent::_construct();
    }
}
