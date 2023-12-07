<?php

/**
 * Auto-register entity declarations from `xml/schema/...*.entityType.php`.
 *
 * @mixinName entity-types-php
 * @mixinVersion 1.0.0
 * @since 5.57
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::entityTypes()
   */
  Civi::dispatcher()->addListener('hook_civicrm_entityTypes', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive() || !is_dir($mixInfo->getPath('xml/schema/CRM'))) {
      return;
    }

    $files = (array) glob($mixInfo->getPath('xml/schema/CRM/*/*.entityType.php'));
    foreach ($files as $file) {
      $entities = include $file;
      foreach ($entities as $entity) {
        $e->entityTypes[$entity['class']] = $entity;
      }
    }
  });

};
