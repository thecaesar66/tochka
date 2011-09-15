<?php
/**
 * Created by JetBrains PhpStorm.
 * User: a.kotynya
 * Date: 9/15/11
 * Time: 11:01 PM
 */
 
class Iscander_Payway_Model_Config_Yesno
{
    public function toOptionArray()
    {
        return array(
            1    => Mage::helper('payway')->__('Да'),
            0    => Mage::helper('payway')->__('Нет'),
        );
    }
}