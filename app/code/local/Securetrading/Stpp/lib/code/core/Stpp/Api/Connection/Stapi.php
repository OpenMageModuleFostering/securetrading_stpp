<?php

class Stpp_Api_Connection_Stapi extends Stpp_Component_Abstract implements Stpp_Api_Connection_BaseInterface {
    const CONNECTION_KEY = 'Stpp_Connection_Stapi';
    
    const CONNECTION_NAME = 'Stpp ST API';
    
    protected $_host = '127.0.0.1'; // ST API Default
    
    protected $_port = 5000; // ST API Default
    
    protected $_alias;
    
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
    
    public function setHost($host) {
        if (!is_string($host)) {
            throw new Stpp_Exception($this->__('The host parameter must be a string.'));
        }
        $this->_host = $host;
    }
    
    public function setPort($port) {
        if (!is_string($port) && !is_int($port)) {
            throw new Stpp_Exception($this->__('The port parameter must be a string or an integer.'));
        }
        $this->_port = $port;
    }
    
    public function sendAndReceiveData($requestString) {
        if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            throw new Stpp_Exception(sprintf($this->__('Unable to create socket.  Reason: "%s".'), socket_strerror(socket_last_error())));
        }
        
        if (socket_connect($socket, $this->_host, $this->_port) === false) {
            throw new Stpp_Exception(sprintf($this->__('Socket unable to connect to %s:%s.  Reason: "%s".'), $this->_host, $this->_port, socket_strerror(socket_last_error())));
        }

        if (socket_write($socket,$requestString) === false) {
            throw new Stpp_Exception(sprintf($this->__('Unable to write to socket.  Reason: "%s".'), socket_strerror(socket_last_error())));
        }

        $responseString = '';

        while ($buffer = socket_read($socket, 2048)) {
            if ($buffer === false) {
                throw new Stpp_Exception(sprintf($this->__('Unable to read from socket.  Reason: "%s".'), socket_strerror(socket_last_error())));
            }
            $responseString .= $buffer;
        }
        socket_close($socket);
        
        return $responseString;
    }
}