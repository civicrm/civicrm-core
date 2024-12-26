<?php

/**
 * Auto-register "templates/" folder.
 *
 * @mixinName smarty
 * @mixinVersion 1.0.3
 * @since 5.71
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

  $register = function($newDirs) {
    $smarty = CRM_Core_Smarty::singleton();
    $v2 = isset($smarty->_version) && version_compare($smarty->_version, 3, '<');
    $templateDirs = (array) ($v2 ? $smarty->template_dir : $smarty->getTemplateDir());
    $templateDirs = array_merge($newDirs, $templateDirs);
    $templateDirs = array_unique(array_map(function($v) {
      $v = str_replace(DIRECTORY_SEPARATOR, '/', $v);
      $v = rtrim($v, '/') . '/';
      return $v;
    }, $templateDirs));
    if ($v2) {
      $smarty->template_dir = $templateDirs;
    }
    else {
      $smarty->setTemplateDir($templateDirs);
    }
  };

  // Let's figure out what environment we're in -- so that we know the best way to call $register().

  if (!empty($GLOBALS['_CIVIX_MIXIN_POLYFILL'])) {
    // Polyfill Loader (v<=5.45): We're already in the middle of firing `hook_config`.
    if ($mixInfo->isActive()) {
      $register([$dir]);
    }
    return;
  }

  if (CRM_Extension_System::singleton()->getManager()->extensionIsBeingInstalledOrEnabled($mixInfo->longName)) {
    // New Install, Standard Loader: The extension has just been enabled, and we're now setting it up.
    // System has already booted. New templates may be needed for upcoming installation steps.
    $register([$dir]);
    return;
  }

  // Typical Pageview, Standard Loader: Defer the actual registration for a moment -- to ensure that Smarty is online.
  // We need to bundle-up all dirs -- Smarty 3/4/5 is inefficient with processing repeated calls to `getTemplateDir()`+`setTemplateDir()`
  if (!isset(Civi::$statics[__FILE__]['event'])) {
    Civi::$statics[__FILE__]['event'] = 'civi.smarty.addPaths.' . md5(__FILE__);
    Civi::dispatcher()->addListener('hook_civicrm_config', function() use ($register) {
      $dirs = [];
      $event = \Civi\Core\Event\GenericHookEvent::create(['dirs' => &$dirs]);
      Civi::dispatcher()->dispatch(Civi::$statics[__FILE__]['event'], $event);
      $register($dirs);
    });
  }

  Civi::dispatcher()->addListener(Civi::$statics[__FILE__]['event'], function($event) use ($mixInfo, $dir) {
    if ($mixInfo->isActive()) {
      array_unshift($event->dirs, $dir);
    }
  });

};
