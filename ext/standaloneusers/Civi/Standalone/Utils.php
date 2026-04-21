<?php

namespace Civi\Standalone;

use CRM_Standaloneusers_ExtensionUtil as E;

class Utils {

  public static function alterHomeMenuItems(&$menu) {
    foreach ($menu as &$item) {
      if (($item['attributes']['name'] ?? NULL) !== 'Home') {
        continue;
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

  public static function validateFavicon($file, $fieldSpec) {
    // Only validate a new file upload.
    if (!is_array($file) || empty($file['type'])) {
      return TRUE;
    }
    if (isset($file['size']) && $file['size'] > 1024 * 1024) {
      throw new \CRM_Core_Exception(E::ts('Favicon must be less than 1MB.'));
    }
    $allowedIconTypes = [
      'image/png' => ['png'],
      'image/x-icon' => ['ico'],
      'image/vnd.microsoft.icon' => ['ico'],
      'image/gif' => ['gif'],
      'image/jpg' => ['jpg', 'jpeg'],
      'image/jpeg' => ['jpg', 'jpeg'],
      // SVG is normally not allowed, but admins can override by adding to `safe_file_extension`.
      // The usual warnings about SVG security apply, so it should be done with caution.
      'image/svg+xml' => ['svg'],
    ];
    $allowedExtensionsSetting = \CRM_Core_OptionGroup::values('safe_file_extension');
    $allowedIconExtensions = [];
    // Reduce $allowedIconTypes based on `safe_file_extension` setting.
    foreach ($allowedIconTypes as $mimeType => $extensions) {
      $extensions = array_intersect($extensions, $allowedExtensionsSetting);
      if (!$extensions) {
        unset($allowedIconTypes[$mimeType]);
      }
      else {
        $allowedIconTypes[$mimeType] = $extensions;
        $allowedIconExtensions = array_unique(array_merge($allowedIconExtensions, $extensions));
      }
    }
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowedIconTypes[$file['type']]) ||
      !in_array($fileExtension, $allowedIconTypes[$file['type']], TRUE)
    ) {
      throw new \CRM_Core_Exception(E::ts('Favicon must be a file of type %1.', [
        1 => implode(', ', array_map('strtoupper', $allowedIconExtensions)),
      ]));
    }
    return TRUE;
  }

}
