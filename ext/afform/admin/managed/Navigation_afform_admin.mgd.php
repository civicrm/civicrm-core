<?php
use CRM_AfformAdmin_ExtensionUtil as E;

$menuItems = [];
$domains = \Civi\Api4\Domain::get(FALSE)
  ->addSelect('id')
  ->execute();
foreach ($domains as $domain) {
  $menuItems[] = [
    'name' => 'Navigation_afform_admin_domain_' . $domain['id'],
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'afform_admin',
        'label' => E::ts('Form Builder'),
        'permission' => [
          'administer CiviCRM',
          'administer afform',
        ],
        'permission_operator' => 'OR',
        'parent_id.name' => 'Customize Data and Screens',
        'weight' => 1,
        'url' => 'civicrm/admin/afform',
        'is_active' => 1,
        'icon' => 'crm-i fa-list-alt',
        'domain_id' => $domain['id'],
      ],
      'match' => ['domain_id', 'name'],
    ],
  ];
}
return $menuItems;
