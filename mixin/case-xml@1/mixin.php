<?php

/**
 * Auto-register "xml/case/*.xml" files.
 *
 * @mixinName case-xml
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
   * @see CRM_Utils_Hook::caseTypes()
   */
  Civi::dispatcher()->addListener('hook_civicrm_caseTypes', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.

    if (!$mixInfo->isActive() || !is_dir($mixInfo->getPath('xml/case'))) {
      return;
    }

    foreach ((array) glob($mixInfo->getPath('xml/case/*.xml')) as $file) {
      $name = basename($file, '.xml');
      if ($name != CRM_Case_XMLProcessor::mungeCaseType($name)) {
        $errorMessage = sprintf("Case-type file name is malformed (%s vs %s)", $name, CRM_Case_XMLProcessor::mungeCaseType($name));
        throw new CRM_Core_Exception($errorMessage);
      }
      $e->caseTypes[$name] = [
        'module' => $mixInfo->longName,
        'name' => $name,
        'file' => $file,
      ];
    }
  });

};
