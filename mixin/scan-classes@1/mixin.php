<?php

/**
 * Scan for files which implement common Civi-PHP interfaces.
 *
 * Specifically, this listens to `hook_scanClasses` and reports any classes with Civi-related
 * interfaces (eg `CRM_Foo_BarInterface` or `Civi\Foo\BarInterface`). For example:
 *
 *   - \Civi\Core\HookInterface
 *   - \Civi\Test\ExampleDataInterface
 *   - \Civi\WorkflowMessage\WorkflowMessageInterface
 *
 * If you are adding this to an existing extension, take care that you meet these assumptions:
 *
 *   - Classes live in 'CRM_' ('./CRM/**.php') or 'Civi\' ('./Civi/**.php').
 *   - Class files only begin with uppercase letters.
 *   - Class files only contain alphanumerics.
 *   - Class files never have multiple dots in the name. ("CRM/Foo.php" is a class; "CRM/Foo.bar.php" is not).
 *   - The ONLY files which match these patterns are STRICTLY class files.
 *   - The ONLY classes which match these patterns are SAFE/INTENDED for use with `hook_scanClasses`.
 *
 * To minimize unintended activations, this only loads Civi interfaces. It skips other interfaces.
 *
 * @mixinName scan-classes
 * @mixinVersion 1.0.0
 * @since 5.52
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */

/**
 * @param \CRM_Extension_MixInfo $mixInfo
 * @param \CRM_Extension_BootCache $bootCache
 */
return function ($mixInfo, $bootCache) {
  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  Civi::dispatcher()->addListener('hook_civicrm_scanClasses', function ($event) use ($mixInfo) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $cache = \Civi\Core\ClassScanner::cache('structure');
    $cacheKey = $mixInfo->longName;
    $all = $cache->get($cacheKey);
    if ($all === NULL) {
      $baseDir = CRM_Utils_File::addTrailingSlash($mixInfo->getPath());
      $all = [];

      \Civi\Core\ClassScanner::scanFolders($all, $baseDir, 'CRM', '_');
      \Civi\Core\ClassScanner::scanFolders($all, $baseDir, 'Civi', '\\');
      if (defined('CIVICRM_TEST')) {
        \Civi\Core\ClassScanner::scanFolders($all, "$baseDir/tests/phpunit", 'CRM', '_');
        \Civi\Core\ClassScanner::scanFolders($all, "$baseDir/tests/phpunit", 'Civi', '\\');
      }
      $cache->set($cacheKey, $all, \Civi\Core\ClassScanner::TTL);
    }

    $event->classes = array_merge($event->classes, $all);
  });

};
