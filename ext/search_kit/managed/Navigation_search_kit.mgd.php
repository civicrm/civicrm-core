<?php
use CRM_Search_ExtensionUtil as E;

$menuItems = [];
$domains = \Civi\Api4\Domain::get(FALSE)
  ->addSelect('id')
  ->execute();
foreach ($domains as $domain) {
  $menuItems[] = [
    'name' => 'Navigation_search_kit_domain_' . $domain['id'],
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('SearchKit'),
        'name' => 'search_kit',
        'url' => 'civicrm/admin/search',
        'icon' => 'crm-i fa-search-plus',
        'permission' => [
          'administer CiviCRM data',
          'administer search_kit',
        ],
        'permission_operator' => 'OR',
        'parent_id.name' => 'Search',
        'is_active' => TRUE,
        'has_separator' => 2,
        'weight' => 13,
        'domain_id' => $domain['id'],
      ],
      'match' => ['domain_id', 'name'],
    ],
  ];
}
return $menuItems;
