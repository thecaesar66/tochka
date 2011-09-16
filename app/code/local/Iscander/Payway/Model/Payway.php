<?php
class Iscander_Payway_Model_Payway extends Mage_Payment_Model_Method_Abstract
{
    const PAYWAY_LIVE_URL       = 'https://secure.webpay.by:8843';
    const PAYWAY_TEST_URL       = 'https://secure.sandbox.webpay.by:8843';
    
    protected $_code 			= 'payway';
    protected $_formBlockType 	= 'payway/form';
    protected $_infoBlockType 	= 'payway/info';
    
    public function canCapture()
    {
        return true;
    }
    
    public function cleanString($string)
    {
        $string_step1 = strip_tags($string);
        $string_step2 = nl2br($string_step1);
        $string_step3 = str_replace("<br />","<br>",$string_step2);
        $cleaned_string = str_replace("\""," inch",$string_step3);        
        return $cleaned_string;
    }
    
    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
                ->setLastTransId($this->getTransactionId());

        return $this;
    }

    public function getIssuerUrls()
    {
        return array("live" => self::PAYWAY_LIVE_URL,
                     "test" => self::PAYWAY_TEST_URL);

    }
    
    public function getPaywayUrl()
    {
        $setIssuerUrls = $this->getIssuerUrls();
        if($this->getConfigData('mode')){
            return $setIssuerUrls["live"];
        }else{
            return $setIssuerUrls["test"];
        }
    }
    
    public function getOrderPlaceRedirectUrl()
    {
          return Mage::getUrl('payway/process/redirect');
    }
    
    protected function getSuccessURL()
    {
        return Mage::getUrl('payway/process/success', array('_secure' => true));
    }

    protected function getFailureURL()
    {
        return Mage::getUrl('payway/process/failure', array('_secure' => true));
    }
    
    protected function getCancelURL()
    {
        return Mage::getUrl('payway/process/cancel', array('_secure' => true));
    }
    
    protected function getIpnURL()
    {
        return Mage::getUrl('payway/process/ipn', array('_secure' => true));
    }

    public function getCustomer()
    {
        if (empty($this->_customer)) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        if (empty($this->_checkout)) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if (empty($this->_quote)) {
            $this->_quote = $this->getCheckout()->getQuote();
        }
        return $this->_quote;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (empty($this->_order)) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
            $this->_order = $order;
        }
        return $this->_order;
    } 
            
    public function getFormFields()
    {
        // get payment mode
        $payment = $this->getQuote()->getPayment();
        $orderItems = $this->getOrder()->getAllItems();

        $time = time();
        $orderId = $this->getOrder()->getIncrementId();
        $signature = $time .
                     $this->getConfigData('wsd_storeid') .
                     $orderId .
                     (int)!$this->getConfigData('mode') .
                     Mage::helper('payway')->getConfig('wsb_currency_id', 'BYR') .
                     round($this->getOrder()->getBaseGrandTotal()).
                     $this->getConfigData('wsb_signature');

        $form_fields = array();
        //prepare variables hidden form fields
        $form_fields['*scart']                          = '';
        $form_fields['wsb_storeid']                     = $this->getConfigData('wsd_storeid');
        $form_fields['wsb_store']                       = $this->getConfigData('wsd_store');
        $form_fields['wsb_order_num']                   = $orderId;
        $form_fields['wsb_currency_id']                 = Mage::helper('payway')->getConfig('wsb_currency_id', 'BYR');
        $form_fields['wsb_language_id']                 = 'russian';
        $form_fields['wsb_seed']                        = $time;
        $form_fields['wsb_signature']                   = md5($signature);
        $form_fields['wsb_return_url']                  = $this->getSuccessURL();
        $form_fields['wsb_cancel_return_url']           = $this->getCancelURL();
        $form_fields['wsb_test']                        = (int)!$this->getConfigData('mode');
        foreach ($orderItems as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $form_fields['wsb_invoice_item_name'][]     = $item->getName();
            $form_fields['wsb_invoice_item_quantity'][] = $item->getQtyToInvoice();
            $form_fields['wsb_invoice_item_price'][]    = round($item->getPrice());
        }
        $form_fields['wsb_total']                       = round($this->getOrder()->getBaseGrandTotal());
        
        //Insert for debugging purposes
        if($this->getConfigData('debug_flag')){
            Mage::helper('payway')->log($form_fields);//for debug purpose
            $resource       = Mage::getSingleton('core/resource');           
            $connection     = $resource->getConnection('core_write');
            $sql            = "INSERT INTO ".$resource->getTableName('payway_api_debug')." SET created_time = ?, request_body = ?, response_body = ?";
            $connection->query($sql, array(date('Y-m-d H:i:s'), $this->getPaywayUrl()."\n".print_r($form_fields, 1), ''));
        }

        return $form_fields;
    }
}