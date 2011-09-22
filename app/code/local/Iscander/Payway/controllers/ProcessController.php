<?php
class Iscander_Payway_ProcessController extends Mage_Core_Controller_Front_Action
{
    protected $_order;
    protected $_paywayResponse = null; //holds the response params from payway

    public function preDispatch()
    {
        $this->_setPaywayResponse();
        return parent::preDispatch();
    }
    
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

    /**
     * @return Iscander_Payway_Model_Payway
     */
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
     * seting response after returning from Webpay
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
     * When a customer chooses Webpay Payment on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $session    = $this->getCheckout();
        $order      = $this->getOrder();
        if (!$order->getId()) {
            $this->norouteAction();
            return;
        }

        $order->addStatusToHistory(
            $order->getStatus(),
            $this->__('Покупатель перенаправлен на Webpay')
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
    
    public function _validateResponse()
    {
        if (isset($this->_paywayResponse['wsb_order_num'])) {
            return true;
        }

        return false;
    }
    
    public function successAction()
    {
        $OrderID       = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order         = Mage::getModel('sales/order');
        $order->loadByIncrementId($OrderID);

        $validateResponse = $this->_validateResponse();
        if($validateResponse) {//check for validation
            /** @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->_paywayResponse['wsb_order_num']);
            $order->addStatusToHistory(
                $order->getStatus(),
                $this->__('Покупатель успешно вернулся с Webpay')
            );

            $transaction = Mage::getModel('payway/transaction');
            $status = $transaction->getStatus($this->_paywayResponse['wsb_tid']);

            switch ($status) {
                case Iscander_Payway_Model_Transaction::WEBPAY_COMPLETED:
                case Iscander_Payway_Model_Transaction::WEBPAY_AUTHORIZED:
                    if ($order->canInvoice()) {
                        $invoice = $order->prepareInvoice();
                        //$invoice->register()->capture();
                        $invoice->register()->pay();
                        Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder())
                            ->save();
                    }
                    $order->addStatusToHistory(
                        $order->getStatus(),
                        $this->__('Платеж прошел успешно.')
                    );
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
                    $order->sendNewOrderEmail();
                    $order->save();

                    $this->_redirect('checkout/onepage/success');
                    break;
                case Iscander_Payway_Model_Transaction::WEBPAY_PENDING:
                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
                    $order->save();
                    break;
                case Iscander_Payway_Model_Transaction::WEBPAY_SYSTEM:
                    break;
                case Iscander_Payway_Model_Transaction::WEBPAY_DECLINED:
                case Iscander_Payway_Model_Transaction::WEBPAY_REFUNDED:
                case Iscander_Payway_Model_Transaction::WEBPAY_VOIDED:
                    $order->cancel();
                    $order->addStatusToHistory($order->getStatus(), $this->__('Возникли проблемы с вашим платежом(Платеж не был одобрен). Тип транзакции:' . Iscander_Payway_Model_Transaction::$_statuses[$status]));
                    $order->save();
                    $session = Mage::getSingleton('checkout/session');
                    $session->addError($this->__('Возникли проблемы с вашим платежом(Платеж не был одобрен). Пожалуйтса попробуте позже.'));
                    $this->_redirect('checkout/cart');
                    break;
            }
            return;
        } else {
            $order->cancel();
            $order->addStatusToHistory($order->getStatus(), $this->__('Возникли проблемы с вашим платежом(Платеж не был одобрен). Пожалуйтса попробуте позже.'));

            $session = Mage::getSingleton('checkout/session');
            $session->addError($this->__('Возникли проблемы с вашим платежом(Платеж не был одобрен). Пожалуйтса попробуте позже.'));
            $this->_redirect('checkout/cart');
            return;
        }

    }

    public function notifyAction()
    {
        $validateResponse = $this->_validateResponse();
        if($validateResponse) {//check for validation
            /** @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->_paywayResponse['wsb_order_num']);
            $order->addStatusToHistory(
                $order->getStatus(),
                $this->__('Пришло извещение о платеже с Webpay')
            );

            $transaction = Mage::getModel('payway/transaction');
            $status = $transaction->getStatus($this->_paywayResponse['wsb_tid']);

            switch ($status) {
                case Iscander_Payway_Model_Transaction::WEBPAY_COMPLETED:
                case Iscander_Payway_Model_Transaction::WEBPAY_AUTHORIZED:
                    if ($order->canInvoice()) {
                        $invoice = $order->prepareInvoice();
                        //$invoice->register()->capture();
                        $invoice->register()->pay();
                        Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder())
                            ->save();
                    }
                    $order->addStatusToHistory(
                        $order->getStatus(),
                        $this->__('Платеж прошел успешно.')
                    );
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
                    $order->sendNewOrderEmail();
                    $order->save();
                    break;
                case Iscander_Payway_Model_Transaction::WEBPAY_PENDING:
                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
                    $order->save();
                    break;
                case Iscander_Payway_Model_Transaction::WEBPAY_SYSTEM:
                    break;
                case Iscander_Payway_Model_Transaction::WEBPAY_DECLINED:
                case Iscander_Payway_Model_Transaction::WEBPAY_REFUNDED:
                case Iscander_Payway_Model_Transaction::WEBPAY_VOIDED:
                    $order->cancel();
                    $order->addStatusToHistory($order->getStatus(), $this->__('Возникли проблемы с вашим платежом(Платеж не был одобрен). Тип транзакции:' . Iscander_Payway_Model_Transaction::$_statuses[$status]));
                    $order->save();
                    break;
            }
        }

        $this->getResponse()->setHeader('HTTP/1.0','200 OK');
    }

    public function cancelAction() {
        $OrderID       = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order         = Mage::getModel('sales/order');
        $order->loadByIncrementId($OrderID);

        if ( !$order->getId() ) {
            $this->_redirect('checkout/cart');
            return false;
        }

        $order->cancel();
        $history = $this->__('Платеж отменен покупателем');
        $order->addStatusToHistory($order->getStatus(), $history);
        $order->save();#Note
        $session = Mage::getSingleton('checkout/session');
        $session->addError($this->__('Ваш плетеж был отменен. Попробуйте позже.'));
        $this->_redirect('checkout/cart');
    }
   
    public function failureAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->addError($this->__('An unexpected error occurred. There is problem with your payment. Please try again.'));
        $this->_redirect('checkout/cart');
    }   

}