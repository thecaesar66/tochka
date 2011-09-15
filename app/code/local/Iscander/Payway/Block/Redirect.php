<?php
class Iscander_Payway_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $standard = $this->getOrder()->getPayment()->getMethodInstance();

        $form     = new Varien_Data_Form();
        $form->setAction($standard->getPaywayUrl())
            ->setId('payway_payment_checkout')
            ->setName('payway_payment_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);

        foreach ($standard->getFormFields() as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $form->addField($field, 'hidden', array('name'=>$field . '[]', 'value'=>$item));
                }
            } else {
                if ($value) {
                    $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
                } else {
                    $form->addField($field, 'hidden', array('name'=>$field));
                }

            }

        }

        $html = '<html><body>';
        $html.= $this->__('You will be redirected to PayWay in a few seconds.');
        $html.= $form->toHtml();

        $html.= '<script type="text/javascript">document.getElementById("payway_payment_checkout").submit();</script>';
        $html.= '</body></html>';

		return $html;
    }
}