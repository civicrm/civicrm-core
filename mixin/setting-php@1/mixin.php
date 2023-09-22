<?php

/**
 * Auto-register "settings/*.setting.php" files.
 *
 * @mixinName setting-php
 * @mixinVersion 1.0.0
 * @since 5.45
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

};
