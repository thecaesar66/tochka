<?php
/**
 * Created by
 * User: a.kotynya
 * Date: 9/22/11
 * Time: 12:45 AM
 */
 
class Iscander_Payway_Model_Transaction extends Varien_Object
{
    const WEBPAY_LIVE_URL       = 'https://webpay.by';
    const WEBPAY_TEST_URL       = 'https://sandbox.webpay.by';

    protected $_userName;
    protected $_password;

    protected $_response;

    public static $_statuses = array(
        '1' => 'completed',
        '2' => 'declined',
        '3' => 'pending',
        '4' => 'authorized',
        '5' => 'refunded',
        '6' => 'system',
        '7' => 'voided'
    );

    const WEBPAY_COMPLETED  = 1;
    const WEBPAY_DECLINED   = 2;
    const WEBPAY_PENDING    = 3;
    const WEBPAY_AUTHORIZED = 4;
    const WEBPAY_REFUNDED   = 5;
    const WEBPAY_SYSTEM     = 6;
    const WEBPAY_VOIDED     = 7;


    public function __construct()
    {
        parent::__construct();

        $this->_userName      = $this->getPayway()->getConfigData('user_name');
        $this->_password      = md5($this->getPayway()->getConfigData('password'));
    }

    public function getIssuerUrls()
    {
        return array("live" => self::WEBPAY_LIVE_URL,
                     "test" => self::WEBPAY_TEST_URL);
    }

    protected function getWebpayUrl()
    {
        $setIssuerUrls = $this->getIssuerUrls();
        if($this->getConfigData('mode')){
            return $setIssuerUrls["live"];
        }else{
            return $setIssuerUrls["test"];
        }
    }

    public function getStatus($transactionId)
    {
        $postData = '*API=&API_XML_REQUEST=' .
            urlencode("<?xml version='1.0' encoding='ISO-8859-1' ?>
                       <wsb_api_request>
                           <command>get_transaction</command>
                           <authorization>
                               <username>{$this->_userName}</username>
                               <password>{$this->_password}</password>
                           </authorization>
                           <fields>echo(
                               <transaction_id>{$transactionId}</transaction_id>
                           </fields>
                       </wsb_api_request>
            ");
        $curl = curl_init($this->getWebpayUrl());
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        try {
            $this->_response = curl_exec($curl);
        } catch (Exception $e) {
            curl_close($curl);
        }

        return $this->_getStatus();
    }

    private function _getStatus()
    {
        $doc = new DOMDocument();
        $doc->loadXML($this->_response);
        $xpath = new DOMXPath($doc);
        $paymentTypes = $xpath->query('/wsb_api_response/fields/payment_type');

        if ($paymentTypes->length == 1) {
            foreach ($paymentTypes as $paymentType) {
                return $paymentType->nodeValue;
            }
        }
    }

    /**
     * @return Iscander_Payway_Model_Payway
     */
    public function getPayway()
    {
        return Mage::getSingleton('payway/payway');
    }

    public function setResponse($response)
    {
        $this->_response = $response;
    }
}
