<?php

class Stpp_Fields_Admin extends Stpp_Component_Abstract implements Stpp_Fields_AdminInterface {
    const FIELD_ALL_SITE_REFERENCE = 'f_all_sref';
    const FIELD_ALL_ENABLE_AUTHORIZE_ONLY = 'f_all_authonly';
    const FIELD_ALL_SETTLE_DUE_DATE = 'f_all_sduedate';
    const FIELD_ALL_SETTLE_STATUS = 'f_all_sstatus';
    
    const FIELD_PPG_USE_SITE_SECURITY = 'f_ppg_usesitesec';
    const FIELD_PPG_SITE_SECURITY_PASSWORD = 'f_ppg_sitesecpw';
    const FIELD_PPG_SITE_SECURITY_ALGORITHM = 'f_ppg_ssecal';
    const FIELD_PPG_USE_NOTIFICATION_HASH = 'f_ppg_usenhash';
    const FIELD_PPG_NOTIFICATION_HASH_PASSWORD = 'f_ppg_nhashpw';
    const FIELD_PPG_NOTIFICATION_HASH_ALGORITHM = 'f_ppg_nhashal';
    const FIELD_PPG_PARENT_CSS = 'f_ppg_pcss';
    const FIELD_PPG_CHILD_CSS = 'f_ppg_ccss';
    const FIELD_PPG_PARENT_JS = 'f_ppg_pjs';
    const FIELD_PPG_CHILD_JS = 'f_ppg_cjs';
    const FIELD_PPG_SUB_SITE_REFERENCE = 'f_ppg_ssref';

    const FIELD_PPG_USE_IFRAME = 'f_ppg_iframe';
    const FIELD_PPG_USE_API = 'f_ppg_useapi';
    
    const FIELD_API_ALL_CONNECTION = 'f_api_a_con';
    const FIELD_API_ALL_USE_3D_SECURE = 'f_api_a_use3d';
    const FIELD_API_ALL_USE_RISK_DECISION = 'f_api_a_userd';
    const FIELD_API_ALL_USE_CARD_STORE = 'f_api_a_usecs';
    const FIELD_API_ALL_USE_AUTO_CARD_STORE = 'f_api_a_autocs';
    const FIELD_API_ALL_DELAY_RISK_DECISION = 'f_api_a_delayrd';
    const FIELD_API_ALL_ACCEPTED_CARDS = 'f_api_a_cards';
    const FIELD_API_ALL_USE_IFRAME = 'f_api_a_iframe';
    
    const FIELD_API_STAPI_ALIAS = 'f_api_s_alias';
    const FIELD_API_STAPI_HOST = 'f_api_s_hsot';
    const FIELD_API_STAPI_PORT = 'f_api_s_port';
    
    const FIELD_API_WS_ALIAS = 'f_api_ws_alias';
    const FIELD_API_WS_USERNAME = 'f_api_ws_un';
    const FIELD_API_WS_PASSWORD = 'f_api_ws_pw';
    const FIELD_API_WS_VERIFY_SSL_CA = 'f_api_ws_verifyssl';
    const FIELD_API_WS_CA_FILE = 'f_api_ws_cafile';
    
    const FIELD_TRANSACTIONSEARCH_USERNAME = 'f_ts_un';
    const FIELD_TRANSACTIONSEARCH_PASSWORD = 'f_ts_pw';
    const FIELD_TRANSACTIONSEARCH_VERIFY_SSL_CA = 'f_ts_verifyssl';
    const FIELD_TRANSACTIONSEARCH_CA_FILE = 'f_ts_cafile';
    
    const FIELD_TYPE_NAME = 'name';
    const FIELD_TYPE_DESCRIPTION = 'desc';
    
    protected $_array = array();
    
    public function __construct() {
        parent::__construct();
        $this->_array = array(
            self::FIELD_ALL_SITE_REFERENCE => array(
                self::FIELD_TYPE_NAME               => $this->__('Site Reference'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('You must obtain a site reference from Secure Trading before you can use this module.  This module can accept either a test site reference or a live site reference.'),
            ),
            self::FIELD_ALL_ENABLE_AUTHORIZE_ONLY => array(
                self::FIELD_TYPE_NAME               => $this->__('Enable Authorize Only'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Enabling authorize only payments will set the settlestatus of the order to 2.  This means the payment will be suspended in the Secure Trading system and will not be captured by your aquiring bank.'),
            ),
            self::FIELD_ALL_SETTLE_DUE_DATE => array(
                self::FIELD_TYPE_NAME               => $this->__('Settle Due Date'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The settle due date is the day that funds held against your customers\' account will be acquired.'),
            ),
            self::FIELD_ALL_SETTLE_STATUS => array(
                self::FIELD_TYPE_NAME               => $this->__('Settle Status'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the settle status that will be applied to this transaction.  This should normally be set to 0.'),
            ),
            self::FIELD_PPG_USE_SITE_SECURITY => array(
                self::FIELD_TYPE_NAME               => $this->__('Use Site Security'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Site security should always be enabled on a live site.  It allows payments to be taken to the Payment Pages safely.  It should only be disabled for testing purposes when using a test site reference.  The site security must be configured correctly both here and on your Secure Trading account.'),
            ),
            self::FIELD_PPG_SITE_SECURITY_PASSWORD => array(
                self::FIELD_TYPE_NAME               => $this->__('Site Security Password'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the password that will be used as part of the site security configuration.  It must match the password configured on your Secure Trading account.'),
            ),
            self::FIELD_PPG_SITE_SECURITY_ALGORITHM => array(
                self::FIELD_TYPE_NAME               => $this->__('Site Security Algorithm'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the hash algorithm that will be used as part of the site security configuration.  It must match the algorithm configured on your Secure Trading account.'),
            ),
            self::FIELD_PPG_USE_NOTIFICATION_HASH => array(
                self::FIELD_TYPE_NAME               => $this->__('Use Notification Hash'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The notification hash should always be enabled on a live site.  It allows notifications to be sent from Secure Trading to your store securely.  It should only be disabled for testing purposes when using a test site reference.  The notification hash must be configured correctly both here and on your Secure Trading account in MyST.'),
            ),
            self::FIELD_PPG_NOTIFICATION_HASH_PASSWORD => array(
                self::FIELD_TYPE_NAME               => $this->__('Notification Hash Password'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the password that will be used as part of the notification hash configuration.  It must match the password configured on your Secure Trading account in MyST.'),
            ),
            self::FIELD_PPG_NOTIFICATION_HASH_ALGORITHM => array(
                self::FIELD_TYPE_NAME               => $this->__('Notification Hash Algorithm'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the hash algorithm that will be used as part of the notification hash configuration.  It must match the algorithm configured on your Secure Trading account in MyST.'),
            ),
            self::FIELD_PPG_PARENT_CSS => array(
                self::FIELD_TYPE_NAME               => $this->__('Parent CSS'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The parent CSS file allows you to make the Payment Pages look the same as your own website by overriding the main CSS stylesheet loaded on the Secure Trading Payment Pages.  Upload this stylesheet to the MyST File Manager and then enter the filename of the file you uploaded to the File Manager here, without the file path or the extension.'),
            ),
            self::FIELD_PPG_CHILD_CSS => array(
                self::FIELD_TYPE_NAME               => $this->__('Child CSS'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The child CSS file allows you to alter the appearance of certain parts of the Payment Pages by extending the main CSS stylesheet loaded on the Secure Trading Payment Pages (CSS Inheritance).  This is useful when you wish to make minor changes to the default look of the Payment Pages.  Upload this stylesheet to the MyST File Manager and then enter the filename of the file you uploaded to the File Manager here, without the file path or the extension.'),
            ),
            self::FIELD_PPG_PARENT_JS => array(
                self::FIELD_TYPE_NAME               => $this->__('Parent JS'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The parent JS file, if provided, replaces the JavaScript file that is loaded when the Payment Pages are viewed by your customers.  You should provide this file when you wish to drastically alter the front-end validation and user-experience provided by the default Secure Trading javascript file.  Upload this file to the MyST File Manager and then enter the filename of the file you uploaded to the File Manager here, without the file path or the extension.'),
            ),
            self::FIELD_PPG_CHILD_JS => array(
                self::FIELD_TYPE_NAME               => $this->__('Child JS'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The child JS file is loaded after the default Payment Pages Javascript file has been loaded.  You can use a child JS file to make small alterations to the default Payment Pages behaviour.  Upload this file to the MyST File Manager and then enter the filename of the file you uploaded to the File Manager here, without the file path or the extension.'),
            ),
            self::FIELD_PPG_SUB_SITE_REFERENCE => array(
                self::FIELD_TYPE_NAME               => $this->__('Sub Site Reference'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The sub site reference, if specified, refers to a set of custom HTML files that will be loaded by the Payment Pages instead of the default HTML files used by  Secure Trading.  These HTML file must be uploaded to the MyST File Manager.  The naming convention of the HTML files is [subsitereference][page type].html.  This field should only contain the sub site reference: the [page type] will be determined by the Payment Pages.'),
            ),
            self::FIELD_PPG_USE_IFRAME => array(
                self::FIELD_TYPE_NAME               => $this->__('Use Iframe'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Enabling this option will make the Payment Pages load in an HTML iframe element.  Using an iframe in combination with parent/child CSS allows you to make it appear as if the Payment Pages are part of your own website.'),
            ),
            self::FIELD_PPG_USE_API => array(
                self::FIELD_TYPE_NAME               => $this->__('Use API with Payment Pages'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Enabling this option will allow your shopping cart to interact with the Secure Trading API using ST API or our WebServices.  This allows you to perform TRANSACTIONUPDATE and REFUND requests from the shopping cart without using MyST.'),
            ),
            
            self::FIELD_API_ALL_CONNECTION => array(
                self::FIELD_TYPE_NAME               => $this->__('Connection'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This lets you choose which connection method to use when connecting to the Secure Trading API.'),
            ),
            self::FIELD_API_ALL_USE_3D_SECURE => array(
                self::FIELD_TYPE_NAME               => $this->__('Use 3D Secure'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Enabling 3D Secure will reduce the possibility of fraudulent transactions being processed on your store and can shift the liability of chargebacks from you (the merchant) to your aquiring bank .  3D Secure must be enabled on your Secure Trading account before you can use this feature.'),
            ),
            self::FIELD_API_ALL_USE_RISK_DECISION => array(
                self::FIELD_TYPE_NAME               => $this->__('Use Risk Decision'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Enabling Risk Decision requests will reduce the possibility of fraudulent transactions by comparing a customer transaction against a fraud-check database and suspending suspicious transactions. Risk Decision requests must be enabled on your Secure Trading account before you can use this feature.'),
            ),
            self::FIELD_API_ALL_USE_CARD_STORE => array(
                self::FIELD_TYPE_NAME               => $this->__('Use Card Store'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Using Card Store will allow your customers to store their debit/credit card information for easy repeat purchasing.  Card information is stored on the Secure Trading servers, not on your own website (a maskedpan and transactionreference only are stored on your site).  Card Store requests must be enabled on your Secure Trading account before you can use this feature.'),
            ),
            self::FIELD_API_ALL_DELAY_RISK_DECISION => array(
                self::FIELD_TYPE_NAME               => $this->__('Use Risk Decision After Auth'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The usual scenario is that a Risk Decision request is sent before an Auth request.  This allows the Auth to be suspended (settlestatus 2) if the Risk Decision reports that the transaction may be fraudulent.  Running a Risk Decision after an Auth (as opposed to before an Auth) provides the risk decision with more information (CC details) to use when determining when a transaction may be fraudulent, but this stops the auth from automatically being suspended if the transaction may be fraudulent.'),
            ),
            self::FIELD_API_ALL_USE_AUTO_CARD_STORE => array(
                self::FIELD_TYPE_NAME               => $this->__('Use Auto Card Store'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Enabling this will make your store automatically process Card Store requests when taking payment: users will not be given the choice to store their card details or not.  Card Store requests must be enabled on your Secure Trading account before you use this feature.  You must also have selected "Enable Card Store".'),
            ),
            self::FIELD_API_ALL_ACCEPTED_CARDS => array(
                self::FIELD_TYPE_NAME               => $this->__('Accepted Cards'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('These are the payment types that you wish to show when a user makes payment with Secure Trading.'),
            ),
            self::FIELD_API_ALL_USE_IFRAME => array(
                self::FIELD_TYPE_NAME               => $this->__('Use Iframe'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Enabling this option will make the 3D Secure ACS URL load in an HTML iframe element (if 3D Secure is enabled).  Using an iframe allows you to make it appear as if the ACS URL is part of your own website.'),
            ),
            
            self::FIELD_API_STAPI_ALIAS => array(
                self::FIELD_TYPE_NAME               => $this->__('ST API Alias'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the alias to use when connecting to Secure Trading through ST API.  Usually this is the same as your site reference.'),
            ),
            self::FIELD_API_STAPI_HOST => array(
                self::FIELD_TYPE_NAME               => $this->__('ST API Host'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the host on which the ST API client is running.  This should usually be set to "localhost".'),
            ),
            self::FIELD_API_STAPI_PORT => array(
                self::FIELD_TYPE_NAME               => $this->__('ST API Port'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the port on which the ST API client is listening.  The default port for ST API is 5000.'),
            ),
            
            self::FIELD_API_WS_ALIAS => array(
                self::FIELD_TYPE_NAME               => $this->__('Web Services Alias'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the alias to use when connecting to Secure Trading through our WebServices.  Usually this is the same as your Web Services username.'),
            ),
            self::FIELD_API_WS_USERNAME => array(
                self::FIELD_TYPE_NAME               => $this->__('Web Services Username'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the username used for server authentication when connecting to the Secure Trading API using our Web Services.'),
            ),
            self::FIELD_API_WS_PASSWORD => array(
                self::FIELD_TYPE_NAME               => $this->__('Web Services Password'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('This is the password used for server authentication when connecting to the Secure Trading API using our Web Services'),
            ),
            self::FIELD_API_WS_VERIFY_SSL_CA => array(
                self::FIELD_TYPE_NAME               => $this->__('Verify SSL Certificates (Web Services)'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('Enable this option to verify that the root Certificate Authority (CA) is trusted and that the verification of the signatures in the certificate chain is successful.  This should always be used in a production environment and should only be disabled for testing purposes.'),
            ),
            self::FIELD_API_WS_CA_FILE => array(
                self::FIELD_TYPE_NAME               => $this->__('SSL CA FILE (Web Services)'),
                self::FIELD_TYPE_DESCRIPTION        => $this->__('The full filepath containing trusted CAs.  The file should be in .PEM/.CRT format.  This is required when the \'Verify SSL Certificates\' config option is enabled.'),
            ),
        	
        	self::FIELD_TRANSACTIONSEARCH_USERNAME => array(
        			self::FIELD_TYPE_NAME			=> $this->__('Transaction Search Username'),
        			self::FIELD_TYPE_DESCRIPTION	=> $this->__('This is the username used for server authentication when downloading the CSV transaction list from MyST.'),
        	),
        	self::FIELD_TRANSACTIONSEARCH_PASSWORD => array(
        			self::FIELD_TYPE_NAME			=> $this->__('Transaction Search Password'),
        			self::FIELD_TYPE_DESCRIPTION	=> $this->__('This is the password used for server authentication when downloading the CSV transaction list from MyST.'),
        	),
        	self::FIELD_TRANSACTIONSEARCH_VERIFY_SSL_CA => array(
        		self::FIELD_TYPE_NAME               => $this->__('Verify SSL Certificates (Transaction Search)'),
        		self::FIELD_TYPE_DESCRIPTION        => $this->__('Enable this option to verify that the root Certificate Authority (CA) is trusted and that the verification of the signatures in the certificate chain is successful.  This should always be used in a production environment and should only be disabled for testing purposes.'),	
        	),
        	self::FIELD_TRANSACTIONSEARCH_CA_FILE => array(
        			self::FIELD_TYPE_NAME               => $this->__('SSL CA FILE (Transaction Search)'),
        			self::FIELD_TYPE_DESCRIPTION        => $this->__('The full filepath containing trusted CAs.  The file should be in .PEM/.CRT format.  This is required when the \'Verify SSL Certificates\' config option is enabled.'),
        	),
        );  
    }
    
    public function getLabel($field) {
        return $this->_getFieldValue($field, self::FIELD_TYPE_NAME);
    }
    
    public function getDescription($field) {
        return $this->_getFieldValue($field, self::FIELD_TYPE_DESCRIPTION);
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