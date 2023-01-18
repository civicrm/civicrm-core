<?php

/**
 * Auto-register "templates/" folder.
 *
 * @mixinName smarty-v2
 * @mixinVersion 1.0.0
 * @since 5.58
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

  // Is it good or bad that this hasn't different static-guard-i-ness than the old 'hook_config' boilerplate?

  if ($mixInfo->isActive()) {
    \Civi::dispatcher()->addListener('hook_civicrm_config', function () use ($dir) {
      \CRM_Core_Smarty::singleton()->addTemplateDir($dir);
    });
  }
  elseif (CRM_Extension_System::singleton()->getManager()->extensionIsBeingInstalledOrEnabled($mixInfo->longName)) {
    \CRM_Core_Smarty::singleton()->addTemplateDir($dir);
  }

};
