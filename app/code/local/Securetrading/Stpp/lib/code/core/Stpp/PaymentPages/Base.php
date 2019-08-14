<?php

class Stpp_PaymentPages_Base extends Stpp_Component_Abstract implements Stpp_PaymentPages_BaseInterface {
    const INTERFACE_NAME = 'Secure Trading Payment Pages';
    
    const PPAGES_CHOICE_URL = 'https://payments.securetrading.net/process/payments/choice';
    
    const PPAGES_DETAILS_URL = 'https://payments.securetrading.net/process/payments/details';
    
    const PPAGES_MOTO_CHOICE_URL = 'https://payments.securetrading.net/login/payments/choice';
    
    const PPAGES_MOTO_DETAILS_URL = 'https://payments.securetrading.net/login/payments/details';
    
    const VERSION_1 = 1;

    const VERSION_2 = 2;
    
    protected $_actionInstance;
    
    protected $_request;
    
    protected $_result;
    
    protected $_httpHelper;
    
    protected $_context;
    
    protected $_usePost = true;
    
    protected $_useSiteSecurity = true;
    
    protected $_siteSecurityPassword = '';
    
    protected $_siteSecurityHashAlgorithm = 'sha256';

    protected $_siteSecurityHashFields = array(
      'currencyiso3a',
      'mainamount',
      'sitereference',
      'settlestatus',
      'settleduedate',
      'authmethod',
      'paypaladdressoverride',
      'strequiredfields',
      'version',
      'stprofile',
      //'ruleidentifier',
      'ruleidentifiers',//TODO-gateway uses ruleidentifier
      'stdefaultprofile',
      'successfulurlredirect',
      'declinedurlredirect',
      'successfulurlnotification',
      'declinedurlnotification',
      'merchantemail',
      'allurlnotification',
      'stextraurlnotifyfields',
      'stextraurlredirectfields',
    );

    protected $_useNotificationHash = true;
    
    protected $_notificationPassword = '';
    
    protected $_notificationHashAlgorithm = 'sha256';
    
    protected $_bypassChoicePage = false;
    
    protected $_useAuthenticatedMoto = true;
    
    public static function getName() {
        return static::INTERFACE_NAME;
    }

    public function setActionInstance(Stpp_PaymentPages_ActionsInterface $actions) {
        $this->_actionInstance = $actions;
        return $this;
    }
    
    protected function _getActionInstance() {
        if ($this->_actionInstance === null)  {
            throw new Stpp_Exception($this->__('The action instance was null.'));
        }
        return $this->_actionInstance;
    }
    
    public function setResult(Stpp_PaymentPages_ResultInterface $result) {
        $this->_result = $result;
        return $this;
    }
    
    protected function _getResult() {
        if ($this->_result === null) {
            throw new Stpp_Exception($this->__('The result object is null.'));
        }
        return $this->_result;
    }
    
    public function setHttpHelper(Stpp_Http_HelperInterface $httpHelper) {
    	$this->_httpHelper = $httpHelper;
    	return $this;
    }
    
    public function _getHttpHelper() {
    	if ($this->_httpHelper === null) {
    		throw new Stpp_Exception($this->__('The HTTP helper is null.'));
    	}
    	return $this->_httpHelper;
    }
    
    public function setUseHttpPost($bool) {
        $this->_usePost = (bool) $bool;
    }
    
    public function setUseSiteSecurityHash($bool) {
        $this->_useSiteSecurity = (bool) $bool;
    }
    
    public function setSiteSecurityHashAlgorithm($siteSecurityHashAlgorithm) {
        $this->_siteSecurityHashAlgorithm = $siteSecurityHashAlgorithm;
        return $this;
    }
    
    public function setSiteSecurityPassword($password) {
        $this->_siteSecurityPassword = $password;
    }
    
    public function overrideDefaultSiteSecurityFields(array $fields) {// intended to be called before setSiteSecurityFields().
      $this->_siteSecurityHashFields = $fields;
    }

    public function setSiteSecurityFields($fields) {
        $this->_siteSecurityHashFields = array_unique(
            array_merge(
                $this->_siteSecurityHashFields,
                $fields
            )
        );
        return $this;
    }
    
    public function setUseNotificationHash($bool) {
        $this->_useNotificationHash = (bool) $bool;
	return $this;
    }
    
    public function setNotificationHashAlgorithm($notificationAlgorithm) {
        $this->_notificationHashAlgorithm = $notificationAlgorithm;
        return $this;
    }
    
    public function setNotificationHashPassword($password) {
        $this->_notificationPassword = $password;
	return $this;
    }
    
    public function setBypassChoicePage($bool) {
        $this->_bypassChoicePage = (bool) $bool;
	return $this;
    }
    
    public function setUseAuthenticatedMoto($bool) {
        $this->_useAuthenticatedMoto = (bool) $bool;
	return $this;
    }
    
    public function run(Stpp_Data_Request $request) {
	$this->_request = $request;

        if ($this->_useSiteSecurity) {
	    $this->_request->set("sitesecurity", $this->_createSiteSecurityHash());
        }

        $data = $this->_request->toArray();
        $redirectUrl = $this->_usePost ? $this->_getHttpPostUrl($data) : $this->_getHttpGetUrl($data);
        
        $result = $this->_getResult()
            ->setRedirectIsPost($this->_usePost)
            ->setRedirectUrl($redirectUrl)
            ->setRedirectData($data)
            ->setRequest($this->_request)
        ;
        return $result;
    }

  protected function _createSiteSecurityHash() {
    $valuesToHash = array();
    foreach($this->_siteSecurityHashFields as $field) {
      $value = $this->_request->get($field);
      if (is_array($value)) {
	foreach($value as $value2) {
	  $valuesToHash[] = $value2;
	}
      }
      else {
	$valuesToHash[] = $value;
      }
    }
    $valuesToHash[] = $this->_siteSecurityPassword;
    return 'g' . hash($this->_siteSecurityHashAlgorithm, implode('', $valuesToHash));
  }

    protected function _getHttpPostUrl() {
       return $this->_getPaymentPagesUrl();
    }
    
    protected function _getHttpGetUrl() {
        $urlArray = array();
        
        foreach($this->_gatewayData as $k => $v) {
            $urlArray[] = urlencode($k) . '=' . urlencode($v);
        }
        return $this->_getPaymentPagesUrl() . '?' . implode('&', $urlArray);
    }
    
    protected function _getPaymentPagesUrl() {
        if (($this->_request->get('accounttypedescription') === Stpp_Types::ACCOUNT_MOTO) && $this->_useAuthenticatedMoto) {
            if ($this->_bypassChoicePage) {
                return self::PPAGES_MOTO_DETAILS_URL;
            }
            else {
                return self::PPAGES_MOTO_CHOICE_URL;
            }
        }
        else {
            if ($this->_bypassChoicePage) {
                return self::PPAGES_DETAILS_URL;
            }
            else {
                return self::PPAGES_CHOICE_URL;
            }
        }
    }
    
    public function runNotification() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Stpp_Exception(sprintf($this->__('%s has been called with the request method "%s".'), __CLASS__ . '::' . __METHOD__, $_SERVER['REQUEST_METHOD']));
        }
        
        if (!isset($_POST['notificationreference'])) {
            throw new Stpp_Exception($this->__('The notificationreference was not posted to the notification.'));
        }
        
        if (!is_string($_POST['notificationreference'])) {
            throw new Stpp_Exception($this->__('The notificationreference was not a string.'));
        }
        
        if ($this->_getActionInstance()->checkIsNotificationProcessed($_POST['notificationreference'])) {
            throw new Stpp_Exception(sprintf($this->__('The notification with notificationereference "%s" has already been processed.'), $_POST['notificationreference']));
        }
        
        if (!isset($_POST['requesttypedescription'])) {
           throw new Stpp_Exception($this->__('The requesttypedescription has not been posted to the notification.'));
        }
	
        $response = $this->_mapResponse();
        
        $this->_getActionInstance()->validateNotification($response);
        
        if ($this->_useNotificationHash) {
            if (!isset($_POST['responsesitesecurity'])) {
                throw new Stpp_Exception($this->__("The notification hash is enabled but the 'responsesitesecurity' field was not posted to the notification script."));
            }
            
            if (!is_string($_POST['responsesitesecurity'])) {
                throw new Stpp_Exception($this->__("The posted responsesitesecurity field was not a string."));
            }
            
            $notificationHash = $this->_createNotificationHash();
            
            if($_POST['responsesitesecurity'] !== $notificationHash) {
               	throw new Stpp_Exception(sprintf($this->__("The notification hashes did not match: %s !== %s."), $_POST['responsesitesecurity'], $notificationHash));
            }
        }
        
        switch($_POST['requesttypedescription']) {
            case Stpp_Types::API_AUTH:
                $this->_getActionInstance()->processAuth($response);
                break;
            case Stpp_Types::API_THREEDQUERY:
                $this->_getActionInstance()->process3dQuery($response);
                break;
            case Stpp_Types::API_RISKDEC:
                $this->_getActionInstance()->processRiskDecision($response);
                break;
            case Stpp_Types::API_REFUND:
                $this->_getActionInstance()->processRefund($response);
                break;
            case Stpp_Types::API_ACCOUNTCHECK:
                $this->_getActionInstance()->processAccountCheck($response);
                break;
		/* // TODO - quick solution to prevent TRANSACTIONUPDATE notifications from being processed by the Magento module.  this should not go into the proper framework release.
            case Stpp_Types::API_TRANSACTIONUPDATE:
                $this->_getActionInstance()->processTransactionUpdate($response);
                break;
		*/
            default:
	      return;
	      //throw new Stpp_Exception(sprintf($this->__('An unhandled responsetype has been provided: "%s".'), $_POST['requesttypedescription'])); // so http 200 returned - TODO - this was for magento module only.
        }
        
        $this->_getActionInstance()->saveNotificationReference($_POST['notificationreference']);
        return $this;
    }
    
    protected function _createResponseSiteSecurityHash(array $fields, $hashAlgorithm, $password) {
    	unset($fields['responsesitesecurity'], $fields['notificationreference']);
    	ksort($fields);
    	array_push($fields, array($password));
	
    	$str = '';
    	foreach($fields as $k => $vArray) {
    		$str .= implode('', $vArray);
    	}
    	return hash($hashAlgorithm, $str);
    }

    protected function _createNotificationHash() {
      return $this->_createResponseSiteSecurityHash($this->_getHttpHelper()->retrievePostParams(), $this->_notificationHashAlgorithm, $this->_notificationPassword);
    }
    
    protected function _createRedirectHash() {
      return $this->_createResponseSiteSecurityHash($this->_getHttpHelper()->retrieveGetParams(), $this->_siteSecurityHashAlgorithm, $this->_siteSecurityPassword);
    }
    
    protected function _mapResponse() {
        $response = new Stpp_Data_Response();
        
        foreach($_POST as $k => $v) {
            $response->set($k, $v);
        }
        
        $this->_getActionInstance()->prepareResponse($response);
        return $response;
    }
    
    public function validateRedirect() {
        if ($_SERVER['REQUEST_METHOD'] !== "GET") {
            throw new Stpp_Exception(sprintf($this->__("The redirect has been run for request method '%s'.")));
        }
        
        if ($this->_useSiteSecurity) {
            if (!isset($_GET['responsesitesecurity'])) {
                throw new Stpp_Exception($this->__("The responsesitesecurity was not sent to the redirect."));
            }
            
            if (!is_string($_GET['responsesitesecurity'])) {
                throw new Stpp_Exception($this->__("The responsesitesecurity sent to the redirect was not a string."));
            }
            
            $redirectHash = $this->_createRedirectHash();
            
            if ($_GET['responsesitesecurity'] !== $redirectHash) {
                throw new Stpp_Exception(sprintf($this->__("The redirect hashes did not match: %s and %s."), $_GET['responsesitesecurity'], $redirectHash));
            }
        }
        return $this;
    }
}