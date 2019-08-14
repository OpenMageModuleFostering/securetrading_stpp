<?php


class Stpp_Api_Connection_Webservices extends Stpp_Component_Abstract implements Stpp_Api_Connection_BaseInterface {
    const CONNECTION_KEY = 'Stpp_Connection_Webservices';
    
    const CONNECTION_NAME = 'Stpp Web Services';
    
    protected $_actionUrl = 'https://webservices.securetrading.net:443/xml/';
    
    protected $_username;
   
    protected $_password;
    
    protected $_alias;
    
    protected $_verifySsl;
     
    protected $_caCertFile;
    
    protected $_rawCustomContextOptions;
    
    public static function getKey() {
        return static::CONNECTION_KEY;
    }
    
    public static function getName() {
        return static::CONNECTION_NAME;
    }
    
    public function setAlias($alias) {
        $this->_alias = $alias;
    }
    
    public function getAlias() {
        return $this->_alias;
    }
    
    public function setUsername($username) {
        $this->_username = $username;
    }
    
    public function setPassword($password) {
        $this->_password = $password;
    }
    
    public function setVerifySsl($bool) {
        $this->_verifySsl = (bool) $bool;
    }
    
    public function setCaCertFile($file) {
        $this->_caCertFile = $file;
    }
    
    public function setRawCustomContextOptions($options) {
        $this->_rawCustomContextOptions = $options;
    }
    
    public function sendAndReceiveData($requestString) {
        $requestString = trim(preg_replace('/>[\s]+</', '><', $requestString)); // Remove all whitespace between elements.
        
        $httpHeaders = array(
            'Host: ' . parse_url($this->_actionUrl, PHP_URL_HOST),
            'Content-type: text/xml;charset=utf-8',
            'Content-Length: ' . strlen($requestString),
            'Authorization: Basic ' . base64_encode($this->_username . ':' . $this->_password),
            'Accept: text/xml',
            'Connection: close',
            'User-Agent: ',
        );
        
        $contextOptions = array(
            'http' => array(
                'protocol_version' => '1.1',
                'method' => 'POST',
                'content' => $requestString,
                'follow_location' => 0,
                'timeout' => 30,
                'ignore_errors' => false,
                'header' => $httpHeaders,
            ),
        );
        
        if ($this->_verifySsl) {
            $contextOptions['ssl'] = array(
                'cafile' => $this->_caCertFile,
                'CN_match' => 'webservices.securetrading.net',  // CN_match will only be checked if 'verify_peer' is set to TRUE.  See https://bugs.php.net/bug.php?id=47030.
                'verify_peer' => true,
            );
        }
        
        if ($this->_rawCustomContextOptions) {
            array_merge_recursive($contextOptions, $this->_rawCustomContextOptions);
        }
        
        $streamContext = stream_context_create($contextOptions);
        
        if (false === ($socket = fopen($this->_actionUrl, 'rb', false, $streamContext))) {
            throw new Stpp_Exception(sprintf($this->__('Could not open socket to "%s".'), $this->_actionUrl));
        }
        
        if (fwrite($socket, $requestString) === false) {
            throw new Stpp_Exception($this->__('The write operation failed.'));
        }
        
        $metaData = stream_get_meta_data($socket);
        $responseHeaders = $metaData['wrapper_data'];
        $responseBody = stream_get_contents($socket);
        
        $match = null;
        
        if (!preg_match("!HTTP/1\.. (\d{3})!", $responseHeaders[0], $match)) {
            throw new Stpp_Exception($this->__('An HTTP response code could not be found.'));
        }

        $httpResponseCode = $match[1];
        
        if ($httpResponseCode != 200) {
            throw new Stpp_Exception(sprintf($this->__('Unexpected HTTP response code %s returned.'), $httpResponseCode));
        }
        
        return $responseBody;
    }
}