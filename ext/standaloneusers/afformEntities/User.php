<?php
// Hacked from
// https://docs.civicrm.org/dev/en/latest/afform/form-builder/
// https://github.com/civicrm/civicrm-core/blob/master/ext/afform/admin/afformEntities/Individual.php
// https://github.com/civicrm/civicrm-core/blob/master/ext/afform/admin/afformEntities/Phone.php
return [
  'type' => 'primary',
  'entity' => 'User',
  'label' => 'User',
  'defaults' => <<<JSON
    {
      "password": "password123haha"
    }
  JSON,
  'icon' => 'fa-user',
  'unique_fields' => ['username'],
];
