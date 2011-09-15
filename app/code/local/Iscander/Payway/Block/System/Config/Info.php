<?php
/**
 * @company    GuruWebSoft-Guru In Web Solutions <www.guruwebsoft.com> 
 * @author     Rajendra K Bhatta <rajen_k_bhtt@hotmail.com>
 *
 * @category   Iscander
 * @package    Iscander_Payway
 */
class Iscander_Payway_Block_System_Config_Info
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    
    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<div style="background:url(\'https://www.payway.com.np/system/images/pay-way-nepal.gif\') no-repeat scroll 15px center #EAF0EE;border:1px solid #CCCCCC;margin-bottom:10px;padding:10px 5px 5px 250px;">
    <h4>About PayWay</h4>
    <p><a href="https://www.payway.com.np/" target="_blank">PayWay</a> is a online payment gateway service provider registered legally as Pay Way Nepal (P) Ltd in Kathmandu, Nepal. References can be obtained from CRO.</p>

<p>PayWay is the safer, easier way to pay and get paid online. The service allows anyone to pay in any way they prefer, including through credit cards, bank accounts, buyer credit or account balances, without sharing financial information.</p>

<p>PayWay has quickly become a popular tool in online payment solutions. Available in Nepal and also in other countries, PayWay enables global ecommerce by making payments possible across different locations, currencies. </p>

<p>PayWay is a name given to our online merchant payment gateway that is launched under the domain payway.com.np. All other domains such as paywaynepal.com.np or paywaynepal.com are secondary domains and will be redirected to payway.com.np</p>

<p>Talking about our merchant payment gateway, the primary focus of this merchant gateway will be to make payments over the internet possible. It is to be noted that PayWay is not an ecommerce website and is solely meant to easy payment over the internet easier over various networks.</p>
<br />
<h4>PayWay Configuration</h4>
<p>Go to System >> Configuration >> Sales >> Payment Methods >> PayWay >> Configure your settings here.</p>
</div>';
        
        return $html;
    }
}
