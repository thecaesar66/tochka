<?php
class Iscander_Payway_Model_Config_Modes
{
    public function toOptionArray()
    {
        return array(
            0    => Mage::helper('payway')->__('Test'),
            1    => Mage::helper('payway')->__('Live'),          
        );
    }
}