<?php

abstract class Securetrading_Stpp_Block_Payment_Info_Abstract extends Mage_Payment_Block_Info {
    public function _construct() {
        parent::_construct();
    }
    
    public function getSpecific($key, $default = null) {
        $specific = $this->getSpecificInformation();
        if (array_key_exists($key, $specific)) {
            return $specific[$key];
        }
        return $default;
    }
    
    public function getAccountTypeDescription() {
        return $this->getSpecific('account_type_description');
    }
    
    protected function _getSecurityCodeStyle($securityCode) {
        if ($securityCode === '2') {
            return 'color: #00AA00;';
        }
        return 'color: #FF0000;';
    }
    
    public function getSecurityAddress() {
        return $this->getInfo()->getMethodInstance()->getIntegration()->getAvsString($this->getSpecific('security_address'));
    }
    
    public function getSecurityAddressStyle() {
        return $this->_getSecurityCodeStyle($this->getSpecific('security_address'));
    }
    
    public function getSecurityPostcode() {
        return $this->getInfo()->getMethodInstance()->getIntegration()->getAvsString($this->getSpecific('security_postcode'));
    }
    
    public function getSecurityPostcodeStyle() {
        return $this->_getSecurityCodeStyle($this->getSpecific('security_postcode'));
    }
    
    public function getSecurityCode() {
        return $this->getInfo()->getMethodInstance()->getIntegration()->getAvsString($this->getSpecific('security_code'));
    }
    
    public function getSecurityCodeStyle() {
        return $this->_getSecurityCodeStyle($this->getSpecific('security_code'));
    }
    
	public function getFraudControlShieldStatusCode() {
		return $this->getSpecific('shield_status_code');
	}
	
	public function getFraudControlShieldStatusCodeStyle() {
		switch ($this->getSpecific('shield_status_code')) {
			case 'ACCEPT':
				$colour = '00AA00';
				break;
			case 'CHALLENGE':
				$colour = 'FF6600';
				break;
			case 'DENY':
			default:
				$colour = 'FF0000';
		}
		return 'color: #' . $colour . ';';
	}
	
    public function getTitle() {
        return $this->getInfo()->getMethodInstance()->getTitle();
    }
    
    public function getTransactionReference() {
        return $this->getSpecific('transaction_reference');
    }

    public function getAuthCode() {
        return $this->getSpecific('authcode');
    }
    
    public function getTransactionReferenceUrl() {
        $transaction = Mage::getModel('securetrading_stpp/transaction')->load($this->getTransactionReference(), 'transaction_reference');
        return Mage::getModel('adminhtml/url')->getUrl('*/securetrading_transactions/single', array('transaction_id' => $transaction->getTransactionId()));
    }
    
    public function getEnrolled() {
        $enrolled = $this->getSpecific('enrolled');
        if (empty($enrolled)) {
        	$enrolled = 'N/A';
        }
        return $enrolled;
    }
    
    public function getStatus() {
		$status = $this->getSpecific('status');
		if (empty($status)) {
			$status = 'N/A';
		}
		return $status;
    }
    
    public function getMystUrl() {
        return 'https://myst.securetrading.net/transactions/singletransaction?transactionreference=' . urlencode($this->getTransactionReference());
    }
    
    public function getPaymentType() {
        return $this->getInfo()->getMethodInstance()->getIntegration()->getCardString($this->getSpecific('payment_type'));
    }
    
    public function getMaskedPan() {
        return $this->getSpecific('masked_pan');
    }
    
    public function getCcLast4() {
        return $this->getSpecific('cc_last_4');
    }
    
    public function getStartMonth() {
        return sprintf("%02s", $this->getSpecific('start_month'));
    }
    
    public function getStartYear() {
        return $this->getSpecific('start_year');
    }
    
    public function getStartDate() {
		$startDate = '';
		$startYear = $this->getStartYear();
		if (!empty($startYear)) {
			$startDate = $this->getStartMonth() . '/' . $this->getStartYear();
		}
		return $startDate;
    }
    
    public function getExpiryMonth() {
        return sprintf("%02s", $this->getSpecific('expiry_month'));
    }
    
    public function getExpiryYear() {
        return $this->getSpecific('expiry_year');
    }
    
    public function getExpiryDate() {
        return $this->getExpiryMonth() . '/' . $this->getExpiryYear();
    }
    
    public function getIssueNumber() {
        return $this->getSpecific('issue_number');
    }
}