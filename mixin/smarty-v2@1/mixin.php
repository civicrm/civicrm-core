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

  $register = function() use ($dir) {
    // This implementation is useful for older versions of CiviCRM. It can be replaced/updated going forward.
    $smarty = CRM_Core_Smarty::singleton();
    if (!is_array($smarty->template_dir)) {
      $this->template_dir = [$smarty->template_dir];
    }
    if (!in_array($dir, $smarty->template_dir)) {
      array_unshift($smarty->template_dir, $dir);
    }
  };

  if ($mixInfo->isActive()) {
    \Civi::dispatcher()->addListener('hook_civicrm_config', $register);
  }
  elseif (CRM_Extension_System::singleton()->getManager()->extensionIsBeingInstalledOrEnabled($mixInfo->longName)) {
    $register();
  }

};
