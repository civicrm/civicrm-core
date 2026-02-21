<?php
// Search Display base module - provides search display wrapper and base services.

// Generate dynamic list of dependencies
// To prevent circular dependencies in Anguar, only these two are declared clientside.
$requires = ['api4', 'ngSanitize'];
// Add all viewable display type Angular modules
foreach (\Civi\Search\Display::getDisplayTypes(['id', 'name'], TRUE) as $displayType) {
  $requires[] = CRM_Utils_String::convertStringToCamel($displayType['name'], FALSE);
}

return [
  'js' => [
    'ang/crmSearchDisplay.module.js',
    'ang/crmSearchDisplay/*.js',
    'ang/crmSearchDisplay/*/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplay',
  ],
  'css' => [
    'css/crmSearchDisplay.css',
  ],
  'basePages' => [],
  'requires' => $requires,
  'exports' => [
    'crm-search-display' => 'E',
  ],
  'settingsFactory' => ['\Civi\Search\Display', 'getModuleSettings'],
];
