<?php

class Stpp_Http_Base extends Stpp_Component_Abstract implements Stpp_Http_BaseInterface {
  protected $_ch;

  protected $_url = '';
    
  protected $_username = '';
   
  protected $_password = '';
    
  protected $_sslVerifyPeer = true;
    
  protected $_sslVerifyHost = 2;
  
  protected $_sslCaCertFile = '';
  
  protected $_sslCheckCertChainForRevokedCerts = true;
  
  protected $_sslDenyRevokedCerts = true;
  
  protected $_sslRevokedCerts = array();
  
  protected $_curlOptions = array();
  
  protected $_httpHeaders = array();
  
  protected $_connectTimeout = 5;
  
  protected $_timeout = 60;
  
  protected $_connectAttempts = 20;

  protected $_connectAttemptsTimeout = 40;

  protected $_sleepUseconds = 1000000;
  
  protected $_curlReadFunctionCalled = false;

  protected $_userAgent = '';

  public function __construct() {
    parent::__construct();
    $this->_timeout += $this->_connectTimeout; // Allow 60 seconds to send and receive data and allow time for one connection attempt.
    $this->_ch = curl_init();
  }
  
  public function setUserAgent($userAgent) {
    $this->_userAgent = $userAgent;
    return $this;
  }

  public function setUrl($url) {
  	$this->_url = $url;
  	return $this;
  }
  
  public function setUsername($username) {
    $this->_username = $username;
    return $this;
  }
    
  public function setPassword($password) {
    $this->_password = $password;
    return $this;
  }
    
  public function setSslVerifyPeer($bool) {
    $this->_sslVerifyPeer = (bool) $bool;
    return $this;
  }
  
  public function setSslVerifyHost($int) {
  	$this->_sslVerifyHost = $int;
  	return $this;
  }
  
  public function setSslCaCertFile($file) {
    $this->_sslCaCertFile = $file;
    return $this;
  }
  
  public function setSslCheckCertChainForRevokedCerts($bool) {
  	$this->_sslCheckCertChainForRevokedCerts = (bool) $bool;
  	return $this;
  }
  
  public function setSslDenyRevokedCerts($bool) {
  	$this->_sslDenyRevokedCerts = (bool) $bool;
  	return $this;
  }
  
  public function setConnectTimeout($connectTimeout) {
    $this->_connectTimeout = $connectTimeout;
    return $this;
  }
  
  public function setTimeout($timeout) {
    $this->_timeout = $timeout;
    return $this;
  }
  
  public function setConnectAttempts($connectAttempts) {
    $this->_connectAttempts = $connectAttempts;
    return $this;
  }
  
  public function setSleepUseconds($sleepUseconds) {
    $this->_sleepUseconds = $sleepUseconds;
    return $this;
  }
  
  public function setCurlOptions(array $options) {
    $this->_curlOptions = $options;
    return $this;
  }
  
  public function setCurlOption($option, $value) {
    $this->_curlOptions[$option] = $value;
    return $this;
  }

  public function setHttpHeaders(array $headers) {
  	$this->_httpHeaders = $headers;
  	return $this;
  }
  
  public function addHttpHeader($header) {
  	$this->_httpHeaders[] = $header;
  	return $this;
  }
  
  public function getHttpHeaders() {
  	return $this->_httpHeaders;
  }
  
  public function httpPost($requestBody = '') {
  	curl_setopt($this->_ch, CURLOPT_POST, 1);
  	if ($this->_sslCheckCertChainForRevokedCerts) {
	        $this->addHttpHeader('Content-Length: ' . strlen($requestBody));
		end($this->_httpHeaders);
		$contentLengthKey = key($this->_httpHeaders);
  		curl_setopt($this->_ch, CURLOPT_INFILE, fopen('data://text/plain,' . urlencode($requestBody), 'r'));
  		curl_setopt($this->_ch, CURLOPT_INFILESIZE, strlen($requestBody));
  		curl_setopt($this->_ch, CURLOPT_READFUNCTION, array($this, 'curlReadFunction'));
  	}
  	else {
  		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $requestBody);
  	}
	
	$this->_prepareCurl();
	$result = $this->_sendAndReceive();
        if ($this->_sslCheckCertChainForRevokedCerts && $contentLengthKey) {
	  unset($this->_httpHeaders[$contentLengthKey]);
        }
        return $result;
  }
  
  public function httpGet() {
  	$this->_prepareCurl();
  	return $this->_sendAndReceive();
  }

  public function getInfo($curlInfoOptConstant = 0) {
    return curl_getinfo($this->_ch, $curlInfoOptConstant);
  }

  /**
   * Used as CURLOPT_READFUNCTION callback.  Should not be used by client code.
   */
  public function curlReadFunction($ch, $fp, $strlen) {
  	if ($this->_curlReadFunctionCalled) {
  		return false;
  	}
  	$this->_curlReadFunctionCalled = true;
  
  	foreach(curl_getinfo($ch, CURLINFO_CERTINFO) as $certInfo) {
  		if (in_array($certInfo['Signature'], $this->_sslRevokedCerts)) {
  			if ($this->_sslDenyRevokedCerts) {
  				curl_setopt($ch, CURLOPT_TIMEOUT,1);
  				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,1);
  				throw new Stpp_Exception(sprintf($this->__('Certificate with signature "%s" rejected.'), $certInfo['Signature']));
  			}
  			else {
  				$this->getDebugLog()->log(sprintf($this->__('Certificate with signature "%s" revoked but TLS connection allowed to continue.'), $certInfo['Signature']));
  			}
  		}
  	}
  	$str = fread($fp, $strlen);
  	return $str;
  }
  
  protected function _sendAndReceive() {
        $this->_curlReadFunctionCalled = false;
  	$result = $this->_sendAndReceiveWithRetries();
  	$this->_checkResult($result);
  	return $result;
  }
  
  protected function _prepareCurl() {
  	curl_setopt($this->_ch, CURLOPT_FAILONERROR, true);
  	curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);
  	curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
  	curl_setopt($this->_ch, CURLOPT_URL, $this->_url);
  	curl_setopt($this->_ch, CURLOPT_CERTINFO, 1);
  	curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, $this->_sslVerifyPeer);
  	curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, $this->_sslVerifyHost);
  	curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $this->_connectTimeout);
  	curl_setopt($this->_ch, CURLOPT_TIMEOUT, $this->_timeout);
  	curl_setopt($this->_ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  	curl_setopt($this->_ch, CURLOPT_USERPWD, $this->_username . ':' . $this->_password);
  	curl_setopt($this->_ch, CURLOPT_USERAGENT, $this->_userAgent);
  	curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $this->getHttpHeaders());
  	
    if (!empty($this->_sslCaCertFile)) {
      curl_setopt($this->_ch, CURLOPT_CAINFO, $this->_sslCaCertFile);
    }

    curl_setopt_array($this->_ch, $this->_curlOptions);
  }
  
  protected function _microsecondsToSeconds($useconds) {
  	return $useconds / 1000000;
  }
  
  protected function _sendAndReceiveWithRetries() {
  	$i = 0;
  	$startTime = time();
  	$execResult = null;
  	
  	while (true) {
  		$i++;
  		$canRetry = false;
  		list($execResult, $curlErrorCode) = $this->_exec();
  		
  		if (in_array($curlErrorCode, array(CURLE_COULDNT_CONNECT, CURLE_OPERATION_TIMEOUTED))) {
  			$this->getDebugLog()->log(sprintf('Failed to connect to %s on attempt %s.  Max attempts: %s.  Connect timeout: %s.  cURL Error: %s.  Sleeping for %s second(s).', $this->_url, $i, $this->_connectAttempts, $this->_connectAttemptsTimeout, $curlErrorCode, $this->_microsecondsToSeconds($this->_sleepUseconds)));
  			usleep($this->_sleepUseconds);
  			if ($this->_canRetry($startTime, $i)) {
  				continue;
  			}
  		}
  		break;
  	}
  	return $execResult;
  }
  
  protected function _exec() {
  	$httpResponseBody = curl_exec($this->_ch);
  	$curlErrorCode = curl_errno($this->_ch);
  	return array($httpResponseBody, $curlErrorCode);
  }
  
  protected function _canRetry($startTime, $i) {
	$timeElapsed = time() - $startTime;
	return ($timeElapsed + $this->_connectTimeout + $this->_microsecondsToSeconds($this->_sleepUseconds)) <= $this->_connectAttemptsTimeout && $i < $this->_connectAttempts;
  }
  
  protected function _checkResult($result) {
    if ($result === false) {
      throw new Stpp_Exception(sprintf($this->__("cURL Error Code: '%s'.  Error Message: '%s'."), curl_errno($this->_ch), curl_error($this->_ch)));
    }
  }
}