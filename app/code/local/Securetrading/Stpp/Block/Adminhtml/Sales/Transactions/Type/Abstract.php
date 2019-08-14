<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Type_Abstract extends Mage_Core_Block_Abstract{
	protected function _addToHtml($title, $child) {
		return '
    		<div class="entry-edit">
    			<div class="entry-edit">
    				<div class="entry-edit-head">
    					<h4 class="icon-head head-edit-form fieldset-legend">' . $title . '</h4>
    				</div>
	    			<div class="log-details-grid">'
	    				. $child . '
	    			</div>
	    		</div>
    		</div>
    	';
	}
}