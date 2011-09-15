<?php
class Iscander_Payway_ProcessController extends Mage_Core_Controller_Front_Action
{
    protected $_order;
	protected $_paywayResponse = null; //holds the response params from payway
	
    /**
     * Get Checkout Singleton
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
  
   	protected function _expireAjax()
    {
        if (!$this->getCheckout()->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }
    
    public function getPayway()
    {
        return Mage::getSingleton('payway/payway');
    }

    /**
     *  Get order
     *
     *  @param    none
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
            $session = Mage::getSingleton('checkout/session');
            $this->_order = Mage::getModel('sales/order');
            $this->_order->loadByIncrementId($session->getLastRealOrderId());
        }
        return $this->_order;
    }

	/**
     * seting response after returning from Payway
     *
     * @param array $response
     * @return object $this
     */
    protected function setPaywayResponse($response)
    {
    	if (count($response)) {
            $this->_paywayResponse = $response;
        }
        return $this;
    }

    /**
     * When a customer chooses Payway Payment on Checkout/Payment page
     *
     */
	public function redirectAction()
	{
		$session 	= $this->getCheckout();
		$order 		= $this->getOrder();        
		if (!$order->getId()) {		     
			$this->norouteAction();
			return;
		}

		$order->addStatusToHistory(
			$order->getStatus(),
			$this->__('Customer was redirected to Payway.')
		);
		$order->save();

		$this->getResponse()
			->setBody($this->getLayout()
				->createBlock('payway/redirect')
				->setOrder($order)
				->toHtml());		
    }
	
    private function _setPaywayResponse()
    {
    	if ($this->getRequest()->isPost()) {
			$this->setPaywayResponse($this->getRequest()->getPost());
		} else if ($this->getRequest()->isGet()) {
			$this->setPaywayResponse($this->getRequest()->getParams());
		}
    }
    
    public function _validateResponse(){
        //validate your response here...
        return true;
    }
    
    public function successAction()
    {   	
    	$OrderID 	   = Mage::getSingleton('checkout/session')->getLastRealOrderId();   		
		$order         = Mage::getModel('sales/order');
        $order->loadByIncrementId($OrderID);
        $validateResponse = $this->_validateResponse();			
		if($validateResponse){//check for validation
			$order = Mage::getModel('sales/order');
            $order->loadByIncrementId($OrderID);
            $order->addStatusToHistory(
	                $order->getStatus(),
					$this->__('Customer successfully returned from Payway.')					
	          	);

            if ($order->canInvoice()) {
                    $invoice = $order->prepareInvoice();
                    //$invoice->register()->capture();
                    $invoice->register()->pay();
                    Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
            }

            $order->sendNewOrderEmail();
            $order->save();

            $this->_redirect('checkout/onepage/success');
            return;	
		}else{
			$order->cancel();
            $order->addStatusToHistory($order->getStatus(), $this->__('There is problem with your payment(Payment was not approved). Please try again.'));

			$session = Mage::getSingleton('checkout/session');
			$session->addError($this->__('There is problem with your payment(Payment was not approved). Please try again.'));
			$this->_redirect('checkout/cart');
    	    return;
		}			
    	
    }
    
    public function cancelAction() {		 
		$OrderID 	   = Mage::getSingleton('checkout/session')->getLastRealOrderId();   		
		$order         = Mage::getModel('sales/order');
        $order->loadByIncrementId($OrderID);
        
		if ( !$order->getId() ) {
			$this->_redirect('checkout/cart');
			return false;
		}		        
        
        $order->cancel();
        $history = $this->__('Payment was canceled by customer');
        $order->addStatusToHistory($order->getStatus(), $history);
        $order->save();#Note
		$session = Mage::getSingleton('checkout/session');
		$session->addError($this->__('Your payment was cancelled. Please try again later.'));
		$this->_redirect('checkout/cart');
	}
   
    public function failureAction()
    {
        $session = Mage::getSingleton('checkout/session');
		$session->addError($this->__('An unexpected error occurred. There is problem with your payment. Please try again.'));    
		$this->_redirect('checkout/cart');
    }   

}