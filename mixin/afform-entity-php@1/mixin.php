<?php

/**
 * Auto-register "afformEntities/*.php" files.
 *
 * @mixinName afform-entity-php
 * @mixinVersion 1.0.0
 * @since 5.50
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  Civi::dispatcher()->addListener('civi.afform_admin.metadata', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive() || !is_dir($mixInfo->getPath('afformEntities'))) {
      return;
    }

    $files = (array) glob($mixInfo->getPath('afformEntities/*.php'));
    foreach ($files as $file) {
      $entityInfo = include $file;
      $entityName = basename($file, '.php');
      $apiInfo = \Civi\AfformAdmin\AfformAdminMeta::getApiEntity($entityInfo['entity'] ?? $entityName);
      // Skip disabled contact types & entities from disabled components/extensions
      if (!$apiInfo) {
        continue;
      }
      $entityInfo += $apiInfo;
      $e->entities[$entityName] = $entityInfo;
    }
  });

};
