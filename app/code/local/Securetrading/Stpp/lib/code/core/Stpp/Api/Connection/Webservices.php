<?php

class Stpp_Api_Connection_Webservices extends Stpp_Http_Base implements Stpp_Api_Connection_BaseInterface {
	const CONNECTION_KEY = 'Stpp_Connection_Webservices';

	const CONNECTION_NAME = 'Stpp Web Services';
	
	protected $_url = 'https://webservices.securetrading.net:443/xml/';
	
	protected $_alias = '';
	
	protected $_httpHeaders = array(
		'Content-type: text/xml;charset=utf-8',
		'Accept: text/xml',
		'Connection: close',
	);
	
	protected $_sslRevokedCerts = array();
	
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
	
	public function sendAndReceiveData($requestString) {
		return $this->httpPost($requestString);
	}
}