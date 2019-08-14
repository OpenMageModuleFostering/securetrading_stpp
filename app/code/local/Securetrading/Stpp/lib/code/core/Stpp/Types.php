<?php

class Stpp_Types implements Stpp_TypesInterface {
    // DOCUMENT: These match the req/response names used in the STPP gateway.
    const API_ERROR = 'ERROR';
    const API_AUTH = 'AUTH';
    const API_THREEDQUERY = 'THREEDQUERY';
    const API_RISKDEC = 'RISKDEC';
    const API_TRANSACTIONUPDATE = 'TRANSACTIONUPDATE';
    const API_REFUND = 'REFUND';
    const API_CARDSTORE = 'STORE';
    const API_ACCOUNTCHECK = 'ACCOUNTCHECK';
    
    const ACCOUNT_ECOM = 'ECOM';
    const ACCOUNT_MOTO = 'MOTO';
    const ACCOUNT_FRAUDCONTROL = 'FRAUDCONTROL';
    const ACCOUNT_CARDSTORE = 'CARDSTORE';
    
    const CARD_AMEX = 'AMEX';
    const CARD_ASTROPAYCARD = 'ASTROPAYCARD';
    const CARD_COAST = 'COAST';
    const CARD_DELTA = 'DELTA';
    const CARD_DINERS = 'DINERS';
    const CARD_DISCOVER = 'DISCOVER';
    const CARD_ELECTRON = 'ELECTRON';
    const CARD_JCB = 'JCB';
    const CARD_KARENMILLEN = 'KARENMILLEN';
    const CARD_LASER = 'LASER';
    const CARD_MAESTRO = 'MAESTRO';
    const CARD_MASTERCARD = 'MASTERCARD';
    const CARD_MASTERCARDDEBIT = 'MASTERCARDDEBIT';
    const CARD_OASIS = 'OASIS';
    const CARD_PIBA = 'PIBA';
    const CARD_PRINCIPLE = 'PRINCIPLE';
    const CARD_PURCHASING = 'PURCHASING';
    const CARD_SHOESTUDIO = 'SHOESTUDIO';
    const CARD_SOLO = 'SOLO';
    const CARD_SWITCH = 'SWITCH';
    const CARD_VISA = 'VISA';
    const CARD_VPAY = 'VPAY';
    const CARD_WAREHOUSE = 'WAREHOUSE';
    
    public static function getRequestAndResponseTypes() {
        return array(
            self::API_ERROR,
            self::API_AUTH,
            self::API_THREEDQUERY,
            self::API_RISKDEC,
            self::API_TRANSACTIONUPDATE,
            self::API_REFUND,
            self::API_CARDSTORE,
        );
    }
    
    public static function getAccountTypeDescriptions() {
        return array(
            self::ACCOUNT_ECOM,
            self::ACCOUNT_MOTO,
            self::ACCOUNT_FRAUDCONTROL,
            self::ACCOUNT_CARDSTORE,
        );
    }
    
    public static function getCardTypes() {
        return array(
            self::CARD_AMEX             => 'American Express',
            self::CARD_ASTROPAYCARD     => 'Astropay',
            self::CARD_COAST            => 'Coast',
            self::CARD_DELTA            => 'Delta',
            self::CARD_DINERS           => 'Diners',
            self::CARD_DISCOVER         => 'Discover',
            self::CARD_ELECTRON         => 'Electron',
            self::CARD_JCB              => 'JCB',
            self::CARD_KARENMILLEN      => 'Karen Millen',
            self::CARD_LASER            => 'Laser',
            self::CARD_MAESTRO          => 'Maestro',
            self::CARD_MASTERCARD       => 'Mastercard',
            self::CARD_MASTERCARDDEBIT  => 'Mastercard Debit',
            self::CARD_OASIS            => 'Oasis',
            self::CARD_PIBA             => 'PIBA',
            self::CARD_PRINCIPLE        => 'Principle',
            self::CARD_PURCHASING       => 'Purchasing',
            self::CARD_SHOESTUDIO       => 'Shoe Studio',
            self::CARD_SOLO             => 'Solo',
            self::CARD_SWITCH           => 'Switch',
            self::CARD_VISA             => 'Visa',
            self::CARD_VPAY             => 'VPay',
            self::CARD_WAREHOUSE        => 'Warehouse',
        );
    }
    
    public static function getTelTypes() {
        return array(
            'H',
            'M',
            'W',
        );
    }
    
    public static function getCustomerShippingMethods() {
        return array(
            'C' => 'Low Cost',
            'D' => 'Designated by Customer',
            'I' => 'International',
            'M' => 'Military',
            'N' => 'Next Day/Overnight',
            'O' => 'Other',
            'P' => 'Store Pickup',
            'T' => '2 day Service',
            'W' => '3 day Service',
        );
    }
    
    public static function getSettleStatuses() {
        return array(
		'0' => '0 - Pending Settlement',
		'1' => '1 - Pending Settlement (Manually Overridden)',
		'2' => '2 - Suspended',
                '3' => '3 - Cancelled',
		'100' => '100 - Settled (Only available for certain aquirers)'
	);
    }
    
    public static function getSettleDueDates() {
        return array(
		0 => 'Process immediately',
		1 => 'Wait 1 day',
		2 => 'Wait 2 days',
		3 => 'Wait 3 days',
		4 => 'Wait 4 days',
		5 => 'Wait 5 days',
		6 => 'Wait 6 days',
		7 => 'Wait 7 days',
	);
    }
    
    public static function getMonths() {
        return array(
            array(
                'numeric' => '01',
                'short' => 'Jan',
                'long' => 'January',
            ),
            array(
                'numeric' => '02',
                'short' => 'Feb',
                'long' => 'February',
            ),
            array(
                'numeric' => '03',
                'short' => 'Mar',
                'long' => 'March',
            ),
            array(
                'numeric' => '04',
                'short' => 'Apr',
                'long' => 'April',
            ),
            array(
                'numeric' => '05',
                'short' => 'May',
                'long' => 'May',
            ),
            array(
                'numeric' => '06',
                'short' => 'Jun',
                'long' => 'June',
            ),
            array(
                'numeric' => '07',
                'short' => 'Jul',
                'long' => 'July',
            ),
            array(
                'numeric' => '08',
                'short' => 'Aug',
                'long' => 'August',
            ),
            array(
                'numeric' => '09',
                'short' => 'Sep',
                'long' => 'September',
            ),
            array(
                'numeric' => '10',
                'short' => 'Oct',
                'long' => 'October',
            ),
            array(
                'numeric' => '11',
                'short' => 'Nov',
                'long' => 'November',
            ),
            array(
                'numeric' => '12',
                'short' => 'Dec',
                'long' => 'December',
            ),
        );
    }
    
    public static function getStartYears() {
        $startYears = array();
		
        for ($i = 20; $i > 0; $i--) {
            $year = date('Y', time() - ($i * (60 * 60 * 24 * 365)));
            $startYears[$i] = $year;
        }
        return $startYears;
    }
    
    public static function getExpiryYears() {
        $expiryYears = array();
	
        for ($i = 0; $i <= 20; $i++) {
            $year = date('Y', time() + ($i * (60 * 60 * 24 * 365)));
            $expiryYears[$i] = $year;
        }
        return $expiryYears;
    }
    
    public static function getAvsCodes() {
        return array(
            '0' => 'Not Given',
            '1' => 'Not Checked',
            '2' => 'Matched',
            '4' => 'Not Matched',
        );
    }
}