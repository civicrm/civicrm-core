<?php

/**
 * Auto-register "xml/Menu/*.xml" files.
 *
 * @mixinName menu-xml
 * @mixinVersion 1.0.0
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::xmlMenu()
   */
  Civi::dispatcher()->addListener('hook_civicrm_xmlMenu', function ($e) use ($mixInfo) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $files = (array) glob($mixInfo->getPath('xml/Menu/*.xml'));
    foreach ($files as $file) {
      $e->files[] = $file;
    }
  });

};
