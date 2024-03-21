<?php

/**
 * Auto-register entity declarations from `schema/*.entityType.php`.
 *
 * @mixinName entity-types-php
 * @mixinVersion 2.0.0
 * @since 5.73
 *
 * Changelog:
 *  - v2.0 scans /schema directory instead of /xml/schema/*
 *  - v2.0 supports only one entity per file
 *  - v2.0 adds 'module' key to each entity
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of BootCache. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::entityTypes()
   */
  Civi::dispatcher()->addListener('hook_civicrm_entityTypes', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive() || !is_dir($mixInfo->getPath('schema'))) {
      return;
    }

    $files = (array) glob($mixInfo->getPath('schema/*.entityType.php'));
    foreach ($files as $file) {
      $entity = include $file;
      $entity['module'] = $mixInfo->longName;
      $e->entityTypes[$entity['name']] = $entity;
    }
  });

};
