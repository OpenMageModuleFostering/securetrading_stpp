<?php

interface Stpp_Http_BaseInterface {
	function setUrl($url);
	function setUsername($username);
	function setPassword($password);
	function setSslVerifyPeer($bool);
	function setSslVerifyHost($bool);
	function setSslCaCertFile($bool);
	function setSslCheckCertChainForRevokedCerts($bool);
	function setSslDenyRevokedCerts($bool);
	function setConnectTimeout($connectTimeout);
	function setTimeout($timeout);
	function setConnectAttempts($connectAttempts);
	function setSleepUseconds($sleepUseconds);
	function setCurlOptions(array $curlOptions);
	function setCurlOption($option, $value);
	function setHttpHeaders(array $httpHeaders);
	function addHttpHeader($header);
	function getHttpHeaders();
	function httpPost($requestBody = '');
	function httpGet();
}