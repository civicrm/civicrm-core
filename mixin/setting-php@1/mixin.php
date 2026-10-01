<?php

/**
 * Auto-register "settings/*.setting.php" files.
 *
 * @mixinName setting-php
 * @mixinVersion 1.1.0
 * @since 6.20
 *
 * Changelog:
 * - v1.0.0 (since 5.45) scans *.setting.php files passively
 * - v1.1.0 (since 6.20) scans *.setting.php files and backfills some properties ("is_domain" and "is_contact")
 *      This version only backfills if the setting "group" matches the extension-name.
 *      Possible future evolutions: core may do a similar backfill for all settings; and/or we may find a way to target by *.setting.php file placement
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::alterSettingsFolders()
   */
  Civi::dispatcher()->addListener('hook_civicrm_alterSettingsFolders', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive()) {
      return;
    }

    $settingsDir = $mixInfo->getPath('settings');
    if (!in_array($settingsDir, $e->settingsFolders) && is_dir($settingsDir)) {
      $e->settingsFolders[] = $settingsDir;
    }
  });

  Civi::dispatcher()->addListener('hook_civicrm_alterSettingsMetaData', function ($e) use ($mixInfo) {
    if (!$mixInfo->isActive()) {
      return;
    }

    foreach ($e->settingsMetaData as &$setting) {
      // Only cleanup metadata for myself
      if (($setting['group'] ?? NULL) !== $mixInfo->shortName) {
        continue;
      }

      // Historically, you were expected to manually set both `is_domain={0,1}` and  `is_contact={0,1}`.
      // If an option is set explicitly, respect that.
      // Otherwise, backfill the options. In total silence, assume `is_domain=1`.
      $setting['is_domain'] ??= (int) isset($setting['is_contact']) ? !$setting['is_contact'] : 1;
      $setting['is_contact'] ??= (int) !$setting['is_domain'];

      // Sanity check
      $sum = ($setting['is_domain'] ?? 0) + ($setting['is_contact'] ?? 0);
      if ($sum !== 1) {
        fprintf(STDERR, "Error (%s): Setting %s has inconsistent scoping (%d)\n", $mixInfo->shortName, $setting['name'], $sum);
      }
    }

  }, 1900);

};
