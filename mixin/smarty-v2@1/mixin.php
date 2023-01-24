<?php

/**
 * Auto-register "templates/" folder.
 *
 * @mixinName smarty-v2
 * @mixinVersion 1.0.0
 * @since 5.59
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {
  $dir = $mixInfo->getPath('templates');
  if (!file_exists($dir)) {
    return;
  }

  $register = function() use ($dir) {
    // This implementation is useful for older versions of CiviCRM. It can be replaced/updated going forward (v1.1+).
    $smarty = CRM_Core_Smarty::singleton();
    if (!is_array($smarty->template_dir)) {
      $this->template_dir = [$smarty->template_dir];
    }
    if (!in_array($dir, $smarty->template_dir)) {
      array_unshift($smarty->template_dir, $dir);
    }
  };

  // Let's figure out what environment we're in -- so that we know the best way to call $register().

  if (!empty($GLOBALS['_CIVIX_MIXIN_POLYFILL'])) {
    // Polyfill Loader (v<=5.45): We're already in the middle of firing `hook_config`.
    if ($mixInfo->isActive()) {
      $register();
    }
    return;
  }

  if (CRM_Extension_System::singleton()->getManager()->extensionIsBeingInstalledOrEnabled($mixInfo->longName)) {
    // New Install, Standard Loader: The extension has just been enabled, and we're now setting it up.
    // System has already booted. New templates may be needed for upcoming installation steps.
    $register();
    return;
  }

  // Typical Pageview, Standard Loader: Defer the actual registration for a moment -- to ensure that Smarty is online.
  \Civi::dispatcher()->addListener('hook_civicrm_config', function() use ($mixInfo, $register) {
    if ($mixInfo->isActive()) {
      $register();
    }
  });

};
