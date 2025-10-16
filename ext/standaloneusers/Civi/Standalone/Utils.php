<?php

namespace Civi\Standalone;

class Utils {

  public static function alterHomeMenuItems(&$menu) {
    foreach ($menu as &$item) {
      if (($item['attributes']['name'] ?? NULL) !== 'Home') {
        continue;
      }

      // use /civicrm/home rather than /civicrm/dashboard
      foreach ($item['child'] as &$subitem) {
        if ($subitem['attributes']['name'] === 'CiviCRM Home') {
          $subitem['attributes']['url'] = 'civicrm/home?reset=1';
        }
      }

      // remove Hide Menu and View My Contact
      $item['child'] = array_filter($item['child'], fn ($subitem) => !in_array($subitem['attributes']['name'], ['Hide Menu', 'View My Contact']));

      // Add My Account.
      $item['child'][] = [
        'attributes' => [
          'label' => ts('My Account'),
          'name' => 'My Account',
          'url' => 'civicrm/my-account',
          'icon' => 'crm-i fa-user',
          'weight' => 2,
        ],
      ];
      return;

    }
  }

}
