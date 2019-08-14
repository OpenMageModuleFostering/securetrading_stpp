<?php

class Securetrading_Stpp_Block_Adminhtml_System_Config_Group extends Mage_Adminhtml_Block_System_Config_Form_Fieldset {
    protected function _getHeaderCommentHtml($element) {
        $image = $this->getSkinUrl('images/securetrading/stpp/st_logo_strapline_200_63.png');
        $version = (string) Mage::getConfig()->getModuleConfig('Securetrading_Stpp')->version;
        $comment = sprintf('<img src="%s" alt="SecureTrading" style="display: block; margin: 0 auto;" /><div class="comment"><b>Module Version:</b> %s</div>', $image, $version);
        return $comment .= parent::_getHeaderCommentHtml($element);
    }
}