<?php

require_once(Mage::getModuleDir('', 'Securetrading_Stpp') . DS . 'lib' . DS . 'Securetrading.php');
Securetrading::init();

$installer = $this;
$installer->startSetup();

$coreConfigDataCollection = Mage::getModel('core/config_data')->getCollection()->addFieldToFilter('path', array(
  array('eq' => 'payment/securetrading_stpp_redirect/connection'),
  array('eq' => 'payment/securetrading_stpp_direct/connection'),
));

$usingStapi = false;
foreach($coreConfigDataCollection as $model) {
  if ($model->getValue() === Stpp_Api_Connection_Stapi::getKey()) {
    $usingStapi = true;
    break;
  }
}

if ($usingStapi) {
  $severity = Mage_AdminNotification_Model_Inbox::SEVERITY_CRITICAL;
  $title = 'Secure Trading Version 3.6 - STAPI removal.  Action required.';
  $description = 'The ST API client is no longer supported as of this release of the Secure Trading module (version 3.6).  Connections to the Secure Trading gateway must now be made using our Web Services.  To connect via the Web Services please enter a valid Web Services username and password in the module configuration (System - Configuration - Payment Methods - Secure Trading).  To obtain a Web Services username please contact support@securetrading.com.  Full release notes are available on Magento Connect - please click \'Read Details\'.';
  $url = 'http://www.magentocommerce.com/magento-connect/securetrading.html';
}
else {
  $severity = Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE;
  $title = 'Secure Trading module updated to version 3.6 .';
  $description = 'Your Secure Trading module has been updated to version 3.6.  This release has removed the deprecated ST API connection method, simplified the module configuration and introduced support for HTML templating of the Payment Pages.  Full release notes are available on Magento Connect - please click \'Read Details\'.';
  $url = 'http://www.magentocommerce.com/magento-connect/securetrading.html';
}

Mage::getModel('adminnotification/inbox')->add($severity, $title, $description, $url);

$severity = Mage_AdminNotification_Model_Inbox::SEVERITY_CRITICAL;
$title = 'Secure Trading Version 3.6 - MyST changes required.';
$description = 'From this release redirects and notifications no longer need to be configured manually in MyST.  Please disable the rules you have configured.  Failing to do so will stop the module from functioning as expected.  Full release notes are available at the provided URL. ';
$url = 'http://www.magentocommerce.com/magento-connect/securetrading.html';
Mage::getModel('adminnotification/inbox')->add($severity, $title, $description, $url);

$coreConfigDataCollection = Mage::getModel('core/config_data')->getCollection()->addFieldToFilter('path', array(
  array('like' => 'payment/securetrading_stpp_redirect/transactionsearch_%'),
  array('like' => 'payment/securetrading_stpp_direct/transactionsearch_%'),
  array('like' => 'payment/securetrading_stpp_redirect/stapi_%'),
  array('like' => 'payment/securetrading_stpp_direct/stapi_%'),
  array('eq' => 'payment/securetrading_stpp_redirect/connection'),
  array('eq' => 'payment/securetrading_stpp_direct/connection'),
  array('eq' => 'payment/securetrading_stpp_redirect/ws_alias'),
  array('eq' => 'payment/securetrading_stpp_direct/ws_alias'),
  array('eq' => 'payment/securetrading_stpp_redirect/use_site_security'),
  array('eq' => 'payment/securetrading_stpp_redirect/use_notification_password'),
  array('eq' => 'payment/securetrading_stpp_redirect/notification_password'),
));

foreach($coreConfigDataCollection as $model) {
  Mage::getModel('core/config')->deleteConfig($model->getPath(), $model->getScope(), $model->getScopeId());
}

Mage::getModel('core/config')->saveConfig('payment/securetrading_stpp_redirect/ppg_version', '1');

$installer->endSetup();