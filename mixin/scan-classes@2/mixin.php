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
 *   - Test directories are not scanned in version 1.1+. See https://github.com/civicrm/civicrm-core/pull/26157
 *
 * To minimize unintended activations, this only loads Civi interfaces. It skips other interfaces.
 *
 * @mixinName scan-classes
 * @mixinVersion 2.0.0
 * @since 6.0
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

use Civi\Core\ClassScanner;

return function ($mixInfo, $bootCache) {
  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  Civi::dispatcher()->addListener('hook_civicrm_scanClasses', function ($event) use ($mixInfo) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $cache = ClassScanner::cache('structure');
    $cacheKey = $mixInfo->longName;
    $all = $cache->get($cacheKey);
    if ($all === NULL) {
      $baseDir = CRM_Utils_File::addTrailingSlash($mixInfo->getPath());
      $all = [];
      ClassScanner::scanFolders($all, $baseDir, 'CRM', '_');

      /**
       * @var CRM_Extension_Mapper $extMap
       */
      $extMap = CRM_Extension_System::singleton()->getMapper();
      /**
       * @var CRM_Extension_Info $info
       */
      $info = $extMap->keyToInfo($mixInfo->longName);
      if (!empty($info->classloader)) {
        foreach ($info->classloader as $mapping) {
          $requiredExtensionsAreInstalled = TRUE;
          if (!empty($mapping['requires-ext'])) {
            foreach ($mapping['requires-ext'] as $requireExtension) {
              if (!$extMap->isActiveModule($requireExtension)) {
                $requiredExtensionsAreInstalled = FALSE;
                break;
              }
            }
          }
          if ($requiredExtensionsAreInstalled && !empty($mapping['path']) && $mapping['type'] == 'psr4') {
            ClassScanner::scanFolders($all, $baseDir, $mapping['path'], '\\');
          }
        }
      }
      $cache->set($cacheKey, $all, ClassScanner::TTL);
    }

    $event->classes = array_merge($event->classes, $all);
  });

};
