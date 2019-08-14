<?php

class Securetrading_Stpp_Block_Adminhtml_System_Config_Fields_Password_Button extends Mage_Adminhtml_Block_System_Config_Form_Field {
  protected $_buttonTemplate = 'securetrading/stpp/system/config/fields/password/button.phtml';

  protected function _prepareLayout() {
    parent::_prepareLayout();
    if (!$this->getTemplate()) {
      $this->setTemplate($this->_buttonTemplate);
    }
    return $this;
  }

  public function getButtonUrl() {
    return Mage::getModel('adminhtml/url')->getUrl('*/securetrading_password/generate', array('_nosecret' => true));
  }

  public function render(Varien_Data_Form_Element_Abstract $element) {
    $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
    return parent::render($element);
  }

  protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
    $originalData = $element->getOriginalData();
    $this->addData(array(
      'button_label' => Mage::helper('securetrading_stpp')->__($originalData['button_label']),
      'html_id' => $element->getHtmlId(),
      'confirm_text' => $originalData['confirm_text'],
      'button_updates' => $originalData['button_updates'],
    ));
    return $this->_toHtml();
  }
}