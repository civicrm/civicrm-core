<?php

/**
 * Auto-register "templates/" folder.
 *
 * @mixinName smarty-v2
 * @mixinVersion 1.0.3
 * @since 5.59
 *
 * @deprecated - it turns out that the mixin is not version specific so the 'smarty'
 * mixin is preferred over smarty-v2 (they are the same but not having the version
 * in the name is less misleading.)
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
    $smarty = CRM_Core_Smarty::singleton();
    // Smarty2 compatibility
    if (isset($smarty->_version) && version_compare($smarty->_version, 3, '<')) {
      $smarty->addTemplateDir($dir);
      return;
    }
    // Avoid calling getTemplateDir on Smarty 3+ when we know we
    // have already registered the directory
    if (empty(Civi::$statics[__FUNCTION__][$dir])) {
      // getTemplateDir returns string or array by reference
      $templateRef = $smarty->getTemplateDir();
      // Dereference and normalize as array
      $templateDirs = (array) $templateRef;
      // Add the dir if not already present
      if (!in_array($dir, $templateDirs, TRUE)) {
        array_unshift($templateDirs, $dir);
        $smarty->setTemplateDir($templateDirs);
      }
      Civi::$statics[__FUNCTION__][$dir] = true;
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
