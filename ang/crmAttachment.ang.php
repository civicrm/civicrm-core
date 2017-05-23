<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array(
  'ext' => 'civicrm',
  'js' => array('ang/crmAttachment.js'),
  'css' => array('ang/crmAttachment.css'),
  'partials' => array('ang/crmAttachment'),
  'settings' => array(
    'token' => \CRM_Core_Page_AJAX_Attachment::createToken(),
  ),
  'requires' => array('angularFileUpload', 'crmResource'),
);
