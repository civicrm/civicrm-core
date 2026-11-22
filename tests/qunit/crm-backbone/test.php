<?php
CRM_Core_Resources::singleton()
  ->addScriptFile('civicrm', 'packages/backbone/backbone.js', 120, 'html-header')
  ->addScriptFile('civicrm', 'packages/backbone/backbone.modelbinder.js', 125, 'html-header', FALSE)
  ->addScriptFile('civicrm', 'js/crm.backbone.js', 130, 'html-header', FALSE);
