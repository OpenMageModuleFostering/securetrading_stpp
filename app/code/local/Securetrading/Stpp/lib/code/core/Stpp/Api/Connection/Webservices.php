<?php

class Stpp_Api_Connection_Webservices extends Stpp_Component_Abstract implements Stpp_Api_Connection_BaseInterface {
  const CONNECTION_KEY = 'Stpp_Connection_Webservices';
    
  const CONNECTION_NAME = 'Stpp Web Services';
  
  protected $_ch;

  protected $_actionUrl = 'https://webservices.securetrading.net:443/xml/';
    
  protected $_username = '';
   
  protected $_password = '';
    
  protected $_alias = '';
    
  protected $_verifySsl = true;
     
  protected $_caCertFile = '';
    
  protected $_options = array();
    
  protected $_connectTimeout = 5;
  
  protected $_timeout = 60;
  
  protected $_connectAttempts = 20;

  protected $_connectAttemptsTimeout = 40;

  protected $_sleepUseconds = 1000000;

  public function __construct() {
    parent::__construct();
    $this->_timeout += $this->_connectTimeout; // Allow 60 seconds to send and receive data and allow time for one connection attempt.
    $this->_ch = curl_init();
  }

  public static function getKey() {
    return static::CONNECTION_KEY;
  }
    
  public static function getName() {
    return static::CONNECTION_NAME;
  }
    
  public function setAlias($alias) {
    $this->_alias = $alias;
    return $this;
  }
    
  public function getAlias() {
    return $this->_alias;
  }
    
  public function setUsername($username) {
    $this->_username = $username;
    return $this;
  }
    
  public function setPassword($password) {
    $this->_password = $password;
    return $this;
  }
    
  public function setVerifySsl($bool) {
    $this->_verifySsl = (bool) $bool;
    return $this;
  }
    
  public function setCaCertFile($file) {
    $this->_caCertFile = $file;
    return $this;
  }
    
  public function setConnectTimeout($connectTimeout) {
    $this->_connectTimeout = $connectTimeout;
    return $this;
  }
  
  public function setTimeout($connectTimeout) {
    $this->_timeout = $connectTimeout;
    return $this;
  }
  
  public function setConnectAttempts($connectAttempts) {
    $this->_connectAttempts = $connectAttempts;
    return $this;
  }

  public function setConnectRetries($connectRetries) {
    $this->_connectRetries = $connectRetries;
    return $this;
  }
  
  public function setSleepUseconds($sleepUseconds) {
    $this->_sleepUseconds = $sleepUseconds;
    return $this;
  }

  public function setOptions(array $options) {
    $this->_options = $options;
    return $this;
  }
  
  public function setOption($option, $value) {
    $this->_options[$option] = $value;
    return $this;
  }

  public function sendAndReceiveData($requestString) {
    $this->_prepareCurl($requestString);
    $result = $this->_send();
    $this->_checkResult($result);
    return $result;
  }

  protected function _prepareCurl($postFields) {
    curl_setopt($this->_ch, CURLOPT_POST, 1);
    curl_setopt($this->_ch, CURLOPT_FAILONERROR, true);
    curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->_ch, CURLOPT_URL, $this->_actionUrl);
    curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, $this->_verifySsl);
    curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $this->_connectTimeout);
    curl_setopt($this->_ch, CURLOPT_TIMEOUT, $this->_timeout);
    curl_setopt($this->_ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($this->_ch, CURLOPT_USERPWD, $this->_username . ':' . $this->_password);
    curl_setopt($this->_ch, CURLOPT_USERAGENT, '');
    curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml;charset=utf-8', 'Accept: text/xml', 'Connection: close'));

    if (!empty($this->_caCertFile)) {
      curl_setopt($this->_ch, CURLOPT_CAINFO, $this->_caCertFile);
    }

    curl_setopt_array($this->_ch, $this->_options);
  }
	
  protected function _send() {
    $i = 0;
    $startTime = time();
    $result = null;

    while (true) {
      $i++;
      $canRetry = false;
      $result = curl_exec($this->_ch);

      if (curl_errno($this->_ch) === CURLE_COULDNT_CONNECT) {
		$this->getDebugLog()->log(sprintf('Failed to connect to %s on attempt %s of %s.  Sleeping for %s second(s).', $this->_actionUrl, $i, $this->_connectAttempts, $this->_sleepUseconds/1000000));
		usleep($this->_sleepUseconds);

		$timeElapsed = time() - $startTime;
		$canRetry = ($timeElapsed + $this->_connectTimeout + ($this->_sleepUseconds / 100000)) < $this->_connectAttemptsTimeout && $i < $this->_connectAttempts;
      }

      if (!$canRetry) {
		break;
      }
    }
    return $result;
  }

  protected function _checkResult($result) {
    if ($result === false) {
      throw new Stpp_Exception(sprintf($this->__("cURL Error Code: '%s'.  Error Message: '%s'."), curl_errno($this->_ch), curl_error($this->_ch)));
    }
  }
}