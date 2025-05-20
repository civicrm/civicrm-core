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

      // remove hide menu
      $item['child'] = array_filter($item['child'], fn ($subitem) => ($subitem['attributes']['name'] !== 'Hide Menu'));

      // add change password
      $item['child'][] = [
        'attributes' => [
          'label' => ts('Change Password'),
          'name' => 'Change Password',
          'url' => 'civicrm/admin/user/password?reset=1',
          'icon' => 'crm-i fa-keyboard',
          'weight' => 2,
        ],
      ];

      return;

    }
  }

}
