<?php

class Securetrading_Stpp_Block_Adminhtml_System_Config_Fieldset_Fields
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
    implements Varien_Data_Form_Element_Renderer_Interface {
    
    protected $_template = 'securetrading/stpp/system/config/fieldset/fields.phtml';
    
    protected function _getCollapseState($element) {
        return false;
    }
    
    protected function _isPaymentEnabled($element) {
		$groupConfig = $this->getGroup($element)->asArray();
      	$activityPath = isset($groupConfig['activity_path']) ? $groupConfig['activity_path'] : '';
      	if (empty($activityPath)) {
        	return false;
      	}
      	$isPaymentEnabled = (string) Mage::getSingleton('adminhtml/config_data')->getConfigDataValue($activityPath);
      	return (bool) $isPaymentEnabled;
    }

    protected function _getHeaderTitleHtml($element) {
      $groupConfig = $this->getGroup($element);
      if ($groupConfig->use_large_fieldset) {
	$headerComment = (string) $groupConfig->header_comment;
	if (isset($groupConfig->help_urls)) {
	  $helpLink = '';
	  foreach($groupConfig->help_urls->children() as $url) {Mage::log($url);
	    $helpLink .= '<a target="_blank" href="' . $url->url . '">' . $url->text . '</a>';
	  }
	}
	else {
	  $helpLink = '';
	}
        $return = '
            <div class="config-heading" >
                 <span style="display: inline-block; width: 16px; float: left; position: relative; left: -6px; top: 8px;">
                     <img src="' .  $this->getSkinUrl('images/securetrading/stpp/success_16_16.png') . '" style="' . ($this->_isPaymentEnabled($element) ? '' : 'visibility: hidden;') . '" />
                 </span>
                 <span style="display: inline-block; width: 120px; float: left; padding-top: 10px;">' .
          			(($filename = (string) $element->getGroup()->image_logo) ? '<img src="' . $this->getSkinUrl('images/securetrading/stpp/' . $filename) . '" />' : '') . '
                 </span>

                <div class="heading">
                    <strong>' . $element->getLegend() . '</strong>' . $helpLink . '
                    <span class="heading-intro">' . $headerComment . '</span>
                </div>
                <div class="button-container" style="line-height: 50px;" >
                    <button
                        type="button"
                        class="button"
                        id="' . $element->getHtmlId() . '-head"
                        onclick="paypalToggleSolution.call(this, \'' . $element->getHtmlId() . '\', \'' . $this->getUrl('*/*/state') . '\'); return false;"
                    >
                        <span class="state-closed">' . $this->__('Configure') . '</span>
                        <span class="state-opened">' . $this->__('Close') . '</span>
                    </button>
                </div>
            </div>
        ';
      }
      else {
	$return = parent::_getHeaderTitleHtml($element);
      }
      return $return;
    }
        
    public function render(Varien_Data_Form_Element_Abstract $element) {
        $fields = Mage::getModel('securetrading_stpp/integration')->getAdminFields();
        $matches = null;
        foreach($element->getElements() as $e) {
            if (preg_match('/^groups\[[^\[\]]+\]\[fields\]\[(.+)\]\[value\]/', $e->getName(), $matches)) {
                $name = $matches[1];
                switch($name) {
                    case 'site_reference':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_ALL_SITE_REFERENCE);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_ALL_SITE_REFERENCE);
                        break;
                    case 'payment_action':
                        $label = $this->__('Payment Action');
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_ALL_ENABLE_AUTHORIZE_ONLY);
                        break;
                    case 'settle_due_date':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_ALL_SETTLE_DUE_DATE);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_ALL_SETTLE_DUE_DATE);
                        break;
                    case 'settle_status':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_ALL_SETTLE_STATUS);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_ALL_SETTLE_STATUS);
                        break;
                    case 'interface':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_ALL_INTERFACE);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_ALL_INTERFACE);
                        break;
                    case 'use_site_security':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_USE_SITE_SECURITY);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_PPG_USE_SITE_SECURITY);
                        break;
                    case 'site_security_password':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_SITE_SECURITY_PASSWORD);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_PPG_SITE_SECURITY_PASSWORD);
                        break;
                    case 'parent_css':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_PARENT_CSS);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_PPG_PARENT_CSS);
                        break;
                    case 'child_css':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_CHILD_CSS);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_PPG_CHILD_CSS);
                        break;
                    case 'parent_js':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_PARENT_JS);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_PPG_PARENT_JS);
                        break;
                    case 'child_js':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_CHILD_JS);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_PPG_CHILD_JS);
                        break;
                    case 'st_profile':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_ST_PROFILE);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_PPG_ST_PROFILE);
                        break;
                    case 'ppg_version':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_VERSION);
			$tooltip = null;
                        break;
		    case 'skip_choice_page':
		       $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_SKIP_CHOICE_PAGE);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_PPG_SKIP_CHOICE_PAGE);
                        break;
                    case 'use_api':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_USE_API);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_PPG_USE_API);
                        break;
                    case 'connection':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_ALL_CONNECTION);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_ALL_CONNECTION);
                        break;
                    case 'use_3d_secure':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_ALL_USE_3D_SECURE);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_ALL_USE_3D_SECURE);
                        break;
                    case 'use_risk_decision':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_ALL_USE_RISK_DECISION);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_ALL_USE_RISK_DECISION);
                        break;
		    case 'use_account_check':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_ALL_USE_ACCOUNT_CHECK);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_ALL_USE_ACCOUNT_CHECK);
                        break;
                    case 'use_card_store':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_ALL_USE_CARD_STORE);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_ALL_USE_CARD_STORE);
                        break;
                    case 'use_auto_card_store':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_ALL_USE_AUTO_CARD_STORE);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_ALL_USE_AUTO_CARD_STORE);
                        break;
                    case 'delay_risk_decision':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_ALL_DELAY_RISK_DECISION);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_ALL_DELAY_RISK_DECISION);
                        break;
                    case 'accepted_cards':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_ALL_ACCEPTED_CARDS);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_ALL_ACCEPTED_CARDS);
                        break;
                    case 'stapi_alias':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_STAPI_ALIAS);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_STAPI_ALIAS);
                        break;
                    case 'stapi_host':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_STAPI_HOST);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_STAPI_HOST);
                        break;
                    case 'stapi_port':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_STAPI_PORT);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_STAPI_PORT);
                        break;
                    case 'ws_alias':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_WS_ALIAS);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_WS_ALIAS);
                        break;
                    case 'ws_username':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_WS_USERNAME);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_WS_USERNAME);
                        break;
                    case 'ws_password':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_WS_PASSWORD);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_WS_PASSWORD);
                        break;
                    case 'ws_verify_ca':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_WS_VERIFY_SSL_CA);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_WS_VERIFY_SSL_CA);
                        break;
                    case 'ws_ca_file':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_WS_CA_FILE);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_WS_CA_FILE);
                        break;
                    case 'transactionsearch_username':
                    	$label = $fields->getLabel(Stpp_Fields_Admin::FIELD_TRANSACTIONSEARCH_USERNAME);
                    	$tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_TRANSACTIONSEARCH_USERNAME);
                    	break;
                    case 'transactionsearch_password':
                    	$label = $fields->getLabel(Stpp_Fields_Admin::FIELD_TRANSACTIONSEARCH_PASSWORD);
                    	$tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_TRANSACTIONSEARCH_PASSWORD);
                    	break;
                    case 'transactionsearch_verify_ca':
                    	$label = $fields->getLabel(Stpp_Fields_Admin::FIELD_TRANSACTIONSEARCH_VERIFY_SSL_CA);
                    	$tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_TRANSACTIONSEARCH_VERIFY_SSL_CA);
                    	break;
                    case 'transactionsearch_ca_file':
                    	$label = $fields->getLabel(Stpp_Fields_Admin::FIELD_TRANSACTIONSEARCH_CA_FILE);
                    	$tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_TRANSACTIONSEARCH_CA_FILE);
                    	break;
                    case 'ppg_use_iframe':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_PPG_USE_IFRAME);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_PPG_USE_IFRAME);
                        break;
		    case 'use_iframe':
                        $label = $fields->getLabel(Stpp_Fields_Admin::FIELD_API_ALL_USE_IFRAME);
                        $tooltip = $fields->getDescription(Stpp_Fields_Admin::FIELD_API_ALL_USE_IFRAME);
                        break;
                    // The following are not retrieved from the framework but are here so the text is not duplicated across the different interfaces in system.xml.
                    case 'active':
                        $label = $this->__('Enabled');
                        $tooltip = $this->__('Enable or disable this payment method.');
                        break;
                    case 'title':
                        $label = $this->__('Title');
                        $tooltip = $this->__('The name of this payment method shown to your customers.');
                        break;
                    case 'description':
                        $label = $this->__('Description');
                        $tooltip = $this->__('The description of this payment method shown to your customers.');
                        break;
                    case 'allowspecific':
                        $label = $this->__('Applicable Countries');
                        $tooltip = $this->__('This payment method can be enabled for all countries or for a specified subset of countries.');
                        break;
                    case 'specificcountry':
                        $label = $this->__('Specific Countries');
                        $tooltip = $this->__('If \'Applicable Countries\' is set to \'Specific Countries\' this list will determine which country this payment method can be used in.');
                        break;
                    case 'ppg_iframe_height':
                    case 'iframe_height':
                        $label = $this->__('Iframe Height');
                        $tooltip = $this->__('The height of the iframe.  Enter one or more numbers followed by "px" or "%".');
                        break;
                    case 'ppg_iframe_width':
                    case 'iframe_width':
                        $label = $this->__('Iframe Width');
                        $tooltip = $this->__('The width of the iframe.  Enter one or more numbers followed by "px" or "%".');
                        break;
                    case 'show_start_date':
                    	$label = $this->__('Show Start Date');
                    	$tooltip = $this->__('Enable this option to show the start date input field on the payment form.');
                    	break;
                    case 'show_issue_number':
                    	$label = $this->__('Show Issue Number');
                    	$tooltip = $this->__('Enable this option to show the issue number input field on the payment form.');
                    	break;
		    case 'allow_billing_agreement_wizard':
		        $label = $this->__('Use Billing Agreement Wizard');
		        $tooltip = $this->__('Enable this option to allow users to create new billing agreements through the customer account management area.');
		        break;
		    case 'use_tokenization':
		      $label = $this->__('Use Tokenization');
		      $tooltip = $this->__('Using tokenization will allow customers who place orders with this payment method to make easy future repeat purchases through the Secue Trading Tokenization payment method.');
		      break;
		    case 'max_saved_cc':
		      $label = $this->__('Max # of Saved Cards');
		      $tooltip = $this->__('The number you enter here is the maximum number of cards (billing agreements) your customers can save per store.');
		      break;
		    case 'save_cc_question':
		      $label = $this->__('"Save CC details?" Question');
		      $tooltip = $this->__('What you will ask your customers when you suggest they save their card details with your store for easy future order placement.');
		      break;
		    case 'config_fallback':
		      $label = $this->__('Config Inheritance');
		      $tooltip = $this->__('Defines which payment method to inherit certain configuration values from.');
		      break;
		    case 'enable_declined_redirect':
		      $label = $this->__('Enable Declined Redirects');
		      $tooltip = $this->__('Enable this if would like your customers to be redirected to your checkout when their card is declined.  The default behavior is for them to remain on the Payment Pages.');
		    case 'show_paymenttype_on_magento':
		      $label = $this->__('Show Accepted Cards Multi-select');
		      $tooltip = $this->__('Enable this to show the list of Accepted Cards in the payment method section of the checkout.  If disabled the payment type will automatically be determined using the customers\' PAN.');
		      break;
                    default:
                        $label = $e->getLabel();
                        $tooltip = $e->getTooltip();
                        break;
                }
                $e->setLabel($label)->setTooltip($tooltip);
            }
        }
        return parent::render($element);   
    }
}