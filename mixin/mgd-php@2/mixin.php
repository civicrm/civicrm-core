<?php

/**
 * Auto-register "**.mgd.php" files.
 *
 * The older (mgd-php@1) and newer (mgd-php@2) are similar in that both load `*.mgd.php` files.
 * However, they differ in how the search:
 *
 * - v1.x does a broad search over the entire extension source-tree.
 * - v2.x does a narrower search in folders which commonly have mgds.
 *   Within 2.x, future increments may add other paths (after vetting `universe` for impacts).
 *
 * @mixinName mgd-php
 * @mixinVersion 2.0.0
 * @since 6.9
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::managed()
   */
  Civi::dispatcher()->addListener('hook_civicrm_managed', function ($event) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive()) {
      return;
    }

    // Optimization: if managed entities were requested for specific module(s),
    // check name and return early if not applicable.
    if ($event->modules && !in_array($mixInfo->longName, $event->modules, TRUE)) {
      return;
    }

    $path = $mixInfo->getPath();
    $mgdFiles = array_merge(
      (array) glob("$path/*.mgd.php"),
      CRM_Utils_File::findFiles("$path/managed", '*.mgd.php'),
      CRM_Utils_File::findFiles("$path/api", '*.mgd.php'),
      CRM_Utils_File::findFiles("$path/CRM", '*.mgd.php'),
      CRM_Utils_File::findFiles("$path/Civi", '*.mgd.php'),
    );

    sort($mgdFiles);
    foreach ($mgdFiles as $file) {
      $es = include $file;
      foreach ($es as $e) {
        if (empty($e['module'])) {
          $e['module'] = $mixInfo->longName;
        }
        if (empty($e['params']['version'])) {
          $e['params']['version'] = '3';
        }
        if (empty($e['source'])) {
          $e['source'] = $file;
        }
        $event->entities[] = $e;
      }
    }
  });

};
