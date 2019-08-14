<?php

class Stpp_Result_Abstract extends Stpp_Component_Abstract implements Stpp_Result_AbstractInterface {
    protected $_isRedirectPost = true;
    
    protected $_redirectUrl = '';
    
    protected $_redirectData = array();
    
    public function getRedirectIsPost() {
        return $this->_isRedirectPost;
    }
    
    public function setRedirectIsPost($bool) {
        $this->_isRedirectPost = (bool) $bool;
        return $this;
    }
    
    public function getRedirectUrl() {
        return $this->_redirectUrl;
    }
    
    public function setRedirectUrl($url) {
        $this->_redirectUrl = $url;
        return $this;
    }
    
    public function getRedirectData() {
        return $this->_redirectData;
    }
    
    public function setRedirectData(array $data) {
        $this->_redirectData = $data;
        return $this;
    }
}