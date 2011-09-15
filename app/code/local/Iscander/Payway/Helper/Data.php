<?php

class Iscander_Payway_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getConfig($field, $default = null){
        $value = Mage::getStoreConfig('payment/payway/'.$field);
        if(!isset($value) or trim($value) == ''){
            return $default;         
        }else{
            return $value;
        }   
	}
    
    public function log($data){
        if(is_array($data) || is_object($data)){
            $data = print_r($data, true);
        }
        Mage::log($data, null, 'payway.log');
	}
}