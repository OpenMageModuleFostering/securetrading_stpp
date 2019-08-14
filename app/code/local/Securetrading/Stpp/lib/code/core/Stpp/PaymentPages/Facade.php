<?php

class Stpp_PaymentPages_Facade extends Stpp_Facade {
    public function newPaymentPages() {
        $ppages = new Stpp_PaymentPages_Base();

        if ($this->_config->has('interfaces/ppages/action_instance')) {
            $ppages->setActionInstance($this->_config->get('interfaces/ppages/action_instance'));
        }
        
        if ($this->_config->has('interfaces/ppages/use_http_post')) {
            $ppages->setUseHttpPost($this->_config->get('interfaces/ppages/use_http_post'));
        }
        
        if ($this->_config->has('interfaces/ppages/sitesecurity/use')) {
            $ppages->setUseSiteSecurityHash($this->_config->get('interfaces/ppages/sitesecurity/use'));
        }

        if ($this->_config->has('interfaces/ppages/sitesecurity/algorithm')) {
            $ppages->setSiteSecurityHashAlgorithm($this->_config->get('interfaces/ppages/sitesecurity/algorithm'));
        }

        if ($this->_config->has('interfaces/ppages/sitesecurity/password')) {
            $ppages->setSiteSecurityPassword($this->_config->get('interfaces/ppages/sitesecurity/password'));
        }

        if ($this->_config->has('interfaces/ppages/sitesecurity/fields')) {
            $ppages->setSiteSecurityFields($this->_config->get('interfaces/ppages/sitesecurity/fields'));
        }

        if ($this->_config->has('interfaces/ppages/notificationhash/use')) {
            $ppages->setUseNotificationHash($this->_config->get('interfaces/ppages/notificationhash/use'));
        }

        if ($this->_config->has('interfaces/ppages/notificationhash/algorithm')) {
            $ppages->setNotificationHashAlgorithm($this->_config->get('interfaces/ppages/notificationhash/algorithm'));
        }

        if ($this->_config->has('interfaces/ppages/notificationhash/password')) {
            $ppages->setNotificationHashPassword($this->_config->get('interfaces/ppages/notificationhash/password'));
        }
	
        if ($this->_config->has('interfaces/ppages/use_authenticated_moto')) {
            $ppages->setUseAuthenticatedMoto($this->_config->get('interfaces/ppages/use_authenticated_moto'));
        }
        
        $ppages->setResult($this->newPaymentPagesResult());
        $ppages->setHttpHelper($this->newHttpHelper());
        return $ppages;
    }
	
    public function newPaymentPagesHelper() {
        $helper = new Stpp_PaymentPages_Helper();
        return $helper;
    }
    
    public function newPaymentPagesResult() {
        return new Stpp_PaymentPages_Result();
    }
    
    public function newHttpHelper() {
    	return new Stpp_Http_Helper();
    }
    
    public function runPaymentPagesStandard(Stpp_Data_Request $request, $adminAction = false) {
        $request = $this->newPaymentPagesHelper()->setAdminAction($adminAction)->prepareStandard($request);
        $result = $this->newPaymentPages()->run($request);
        return $result;
    }
}