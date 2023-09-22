<?php

/**
 * Auto-register "*.theme.php" files.
 *
 * @mixinName theme-php
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
   * @see CRM_Utils_Hook::themes()
   */
  Civi::dispatcher()->addListener('hook_civicrm_themes', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive()) {
      return;
    }
    $files = (array) glob($mixInfo->getPath('*.theme.php'));
    foreach ($files as $file) {
      $themeMeta = include $file;
      if (empty($themeMeta['name'])) {
        $themeMeta['name'] = basename($file, '.theme.php');
      }
      if (empty($themeMeta['ext'])) {
        $themeMeta['ext'] = $mixInfo->longName;
      }
      $e->themes[$themeMeta['name']] = $themeMeta;
    }
  });

};
