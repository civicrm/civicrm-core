<?php

/**
 * The "setting-admin" mixin defines a standard idiom for managing extension settings:
 *
 * 1. Create a permission "administer {myext}" ("Administer {My Extension}").
 * 2. Create a page "civicrm/admin/setting/{myext}" (via `CRM_Admin_Form_Generic`)
 * 3. Assign all settings from "{myext}" to appear on the page.
 *
 * If you don't like the defaults, then there are a few override points:
 *
 * - If you manually create permission "administer {myext}", then your label/description takes precedence.
 * - If you manually register route "civicrm/admin/setting/{myext}", then your definition takes precedence.
 * - If you manually configure settings with `settings_page`, then your page and weight takes precedence.
 *   (To make a hidden settings, specify `settings_page => []`.)
 *
 * @mixinName setting-admin
 * @mixinVersion 1.0.0
 * @since 5.67
 *
 * @param \CRM_Extension_MixInfo $mixInfo
 * @param \CRM_Extension_BootCache $bootCache
 */
return function ($mixInfo, $bootCache) {

  // We need to cache some metadata from 'info.xml'
  $title = $bootCache->define('settingadmin_title_' . $mixInfo->longName, function() use ($mixInfo) {
    $info = CRM_Extension_System::singleton()->getMapper()->keyToInfo($mixInfo->longName);
    return empty($info->label) ? $mixInfo->longName : $info->label;
  });

  // Register the setting page ("civicrm/admin/setting/{myext}").
  Civi::dispatcher()->addListener('&hook_civicrm_alterMenu', function (array &$items) use ($mixInfo, $title) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $path = 'civicrm/admin/setting/' . $mixInfo->shortName;
    if (!isset($items[$path])) {
      $perm = 'administer ' . $mixInfo->shortName;
      $items[$path] = [
        'title' => ts('Administer %1', [1 => $title]),
        'page_callback' => 'CRM_Admin_Form_Generic',
        'access_arguments' => [['administer CiviCRM', $perm], 'or'],
      ];
    }
  }, -1000);

  // Define a permission "administer {myext}"
  Civi::dispatcher()->addListener('&hook_civicrm_permission', function (array &$permissions) use ($mixInfo, $title) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $perm = 'administer ' . $mixInfo->shortName;
    if (!isset($permissions[$perm])) {
      $permissions[$perm] = ts('%1: Administer settings', [1 => $title]);
    }
  }, -1000);

  // Any settings with "group=={myext}" should be added to our setting page (unless overridden).
  // By default, 'weight' is based on the order-of-declaration (spaced out with increments of 10).
  Civi::dispatcher()->addListener('&hook_civicrm_alterSettingsMetaData', function(array &$settingsMetaData) use ($mixInfo) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $weight = 1000;
    $weightInterval = 10;

    foreach ($settingsMetaData as &$setting) {
      if (($setting['group'] ?? '') === $mixInfo->shortName) {
        if (!array_key_exists('settings_pages', $setting)) {
          $setting['settings_pages'][$mixInfo->shortName] = [
            'weight' => $weight,
          ];
          $weight += $weightInterval;
        }
      }
    }
  }, -1000);

};
