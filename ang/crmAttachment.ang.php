<?php
// This file declares an Angular module which can be autoloaded
return [
  'ext' => 'civicrm',
  'js' => ['ang/crmAttachment.js'],
  'css' => ['ang/crmAttachment.css'],
  'partials' => ['ang/crmAttachment'],
  'settingsFactory' => ['CRM_Core_Page_AJAX_Attachment', 'angularSettings'],
  'requires' => ['angularFileUpload', 'crmResource'],
];
