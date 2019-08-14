<?php

class Stpp_Fields_Frontend extends Stpp_Component_Abstract implements Stpp_Fields_FrontendInterface {
    const FIELD_PAYMENT_TYPE = 'f_paymenttype';
    const FIELD_PAN = 'f_pan';
    const FIELD_EXPIRY_DATE = 'f_expirydate';
    const FIELD_EXPIRY_MONTH = 'f_expirymonth';
    const FIELD_EXPIRY_YEAR = 'f_expiryyear';
    const FIELD_START_DATE = 'f_startdate';
    const FIELD_START_MONTH = 'f_startmonth';
    const FIELD_START_YEAR = 'f_startyear';
    const FIELD_SECURITY_CODE = 'f_securitycode';
    const FIELD_ISSUE_NUMBER = 'f_issuenumber';
    const FIELD_USE_SAVED_CARD_0 = 'f_usesavedcard0';
    const FIELD_USE_SAVED_CARD_1 = 'f_usesavedcard1';
    const FIELD_SAVE_CARD_0 = 'f_savecard0';
    const FIELD_SAVE_CARD_1 = 'f_savecard1';
    
    const FIELD_TYPE_LABEL = 'label';
    const FIELD_TYPE_DESCRIPTION = 'desc';
    
    protected $_array = array();
    
    public function __construct() {
        parent::__construct();
        $this->_array = array(
            self::FIELD_PAYMENT_TYPE => array(
                self::FIELD_TYPE_LABEL              => $this->__('Card Type'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the bank that issued you with your credit/debit card.  The issuer logo/details are shown on your card.'),
            ),
            self::FIELD_PAN => array(
                self::FIELD_TYPE_LABEL              => $this->__('Card Number'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the long 15 or 16 digit number on the front of your card.'),
            ),
            self::FIELD_START_DATE => array(
                self::FIELD_TYPE_LABEL              => $this->__('Start Date'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The month and year that your card was issued.  This can be found on the front of your card.'),
            ),
            self::FIELD_START_MONTH => array(
                self::FIELD_TYPE_LABEL              => $this->__('Start Month'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The month that your card was issued.  This can be found on the front of your card.'),
            ),
            self::FIELD_START_YEAR => array(
                self::FIELD_TYPE_LABEL              => $this->__('Start Year'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The year that your card was issued.  This can be found on the front of your card.'),
            ),
            self::FIELD_EXPIRY_DATE => array(
                self::FIELD_TYPE_LABEL              => $this->__('Expiry Date'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The month and year that your card expires.  This can be found on the front of your card.'),
            ),
            self::FIELD_EXPIRY_MONTH => array(
                self::FIELD_TYPE_LABEL              => $this->__('Expiry Month'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The month that your card expires.  This can be found on the front of your card.'),
            ),
            self::FIELD_EXPIRY_YEAR => array(
                self::FIELD_TYPE_LABEL              => $this->__('Expiry Year'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The year that your card expires.  This can be found on the front of your card.'),
            ),
            self::FIELD_SECURITY_CODE => array(
                self::FIELD_TYPE_LABEL              => $this->__('Security Code'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This can be found on the back of your card.  It is a 3 or 4 digit number.'),
            ),
            self::FIELD_ISSUE_NUMBER => array(
                self::FIELD_TYPE_LABEL              => $this->__('Issue Number'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('If present, this will be on the front of your card.  Not all credit/debit cards have an issue number.'),
            ),
            self::FIELD_USE_SAVED_CARD_0 => array(
                self::FIELD_TYPE_LABEL              => $this->__('Pay with a new card.'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Select this option to enter new credit/debit card details.'),
            ),
            self::FIELD_USE_SAVED_CARD_1 => array(
                self::FIELD_TYPE_LABEL              => $this->__('Pay with a saved card.'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Select this option to choose from one of your saved credit/debit cards.'),
            ),
            self::FIELD_SAVE_CARD_0 => array(
                self::FIELD_TYPE_LABEL              => $this->__('Do not save my card details.'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Select this option to not save your card details with us.  By selecting this option you will not be able to take advantage of our easier and faster checkout process when placing future orders.'),
            ),
            self::FIELD_SAVE_CARD_1 => array(
                self::FIELD_TYPE_LABEL              => $this->__('Save my card details securely.'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Select this option to securely save your card details with us.  This allows you to make future purchases quickly and easily.'),
            ),
        );
    }
    
    public function getLabel($field) {
        return $this->_getFieldValue($field, self::FIELD_TYPE_LABEL);
    }
    
    public function getDescription($field) {
        return $this->_getFieldValue($key, self::FIELD_TYPE_DESCRIPTION);
    }
    
    protected function _getFieldValue($field, $type) {
        if (!array_key_exists($field, $this->_array)) {
            return false;
        }
        if (!array_key_exists($type, $this->_array[$field])) {
            return false;
        }
        return $this->_array[$field][$type];
    }
}