<?php

class Securetrading_Stpp_Block_Payment_Redirect_Info extends Securetrading_Stpp_Block_Payment_Info_Abstract {
    public function _construct() {
        parent::_construct();
        $this->setTemplate('securetrading/stpp/payment/redirect/info.phtml');
    }

    public function toPdf() {
      $this->setTemplate('securetrading/stpp/payment/redirect/info_pdf.phtml');
      return $this->toHtml();
    }
}