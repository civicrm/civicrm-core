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
  $classloaderRules = [];
  $infoXmlFile = $mixInfo->getPath('info.xml');
  [$info, $error] = \CRM_Utils_XML::parseFile($infoXmlFile);
  if ($error) {
    Civi::log()->error("Failed to parse $infoXmlFile");
  }
  if (isset($info->classloader)) {
    foreach ($info->classloader as $classloader) {
      foreach ($classloader->children() as $psr) {
        $type = 'psr0';
        if ($psr->getName() == 'psr4') {
          $type = 'psr4';
        }
        $path = (string) $psr->attributes()->path;
        if (empty($path) || $path == '.') {
          $path = 'CRM';
        }
        $rule = [
          'type' => $type,
          'prefix' => (string) $psr->attributes()->prefix,
          'path' => $path,
          'class-delim' => '\\',
          'exclude-prefixes' => [],
          'include-rules' => [],
          'include-rules-match' => 'all',
        ];
        foreach ($psr->children() as $child) {
          if ($child->getName() == 'scanner-exclude-prefix') {
            $rule['exclude-prefixes'][] = (string) $child;
          }
          elseif ($child->getName() == 'scanner-include') {
            if ($child->attributes()->match) {
              $rule['include-rules-match'] = (string) $child->attributes()->match;
            }
            foreach ($child->children() as $includeRule) {
              if ($includeRule->getName() == 'ext') {
                $minVersion = '*';
                $maxVersion = '*';
                if ($includeRule->attributes()->min_ver) {
                  $minVersion = (string) $includeRule->attributes()->min_ver;
                }
                if ($includeRule->attributes()->max_ver) {
                  $maxVersion = (string) $includeRule->attributes()->max_ver;
                }
                $rule['include-rules'][] = [
                  'ext' => (string) $includeRule,
                  'min-ver' => $minVersion,
                  'max-var' => $maxVersion,
                ];
              }
            }
          }
        }
        $classloaderRules[] = $rule;
      }
    }
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  Civi::dispatcher()->addListener('hook_civicrm_scanClasses', function ($event) use ($mixInfo, $classloaderRules) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $cache = ClassScanner::cache('structure');
    $cacheKey = $mixInfo->longName;
    $all = $cache->get($cacheKey);
    if ($all === NULL) {
      $baseDir = CRM_Utils_File::addTrailingSlash($mixInfo->getPath());
      $all = [];

      /**
       * @var CRM_Extension_Mapper $extMap
       */
      $extMap = CRM_Extension_System::singleton()->getMapper();
      foreach ($classloaderRules as $mapping) {
        $excludeRegEx = NULL;
        if (is_array($mapping['exclude-prefixes']) && count($mapping['exclude-prefixes'])) {
          $excludeRegEx = '(' . implode("|", array_map('preg_quote', $mapping['exclude-prefixes'])) . ')';
        }
        $requiredExtensionsAreInstalled = TRUE;
        if (count($mapping['include-rules'])) {
          if ($mapping['include-rules-match'] == 'any') {
            $requiredExtensionsAreInstalled = FALSE;
          }
          foreach ($mapping['include-rules'] as $requireExtension) {
            $extIsValid = FALSE;
            if ($extMap->isActiveModule($requireExtension['ext'])) {
              $reqExtInfo = $extMap->keyToInfo($requireExtension['ext']);
              $minVersionMatch = TRUE;
              $maxVersionMatch = TRUE;
              if ($requireExtension['min_ver'] != '*' && !version_compare($requireExtension['min_ver'], $reqExtInfo->version, '<')) {
                $minVersionMatch = FALSE;
              }
              if ($requireExtension['max_ver'] != '*' && !version_compare($requireExtension['max_ver'], $reqExtInfo->version, '>')) {
                $maxVersionMatch = FALSE;
              }
              $extIsValid = $minVersionMatch && $maxVersionMatch;
            }
            if ($mapping['include-rules-match'] == 'any' && $extIsValid) {
              $requiredExtensionsAreInstalled = TRUE;
            }
            elseif ($mapping['include-rules-match'] == 'all' && !$extIsValid) {
              $requiredExtensionsAreInstalled = FALSE;
            }
          }
        }
        if ($requiredExtensionsAreInstalled && !empty($mapping['path'])) {
          ClassScanner::scanFolders($all, $baseDir, $mapping['path'], $mapping['class-delim'], $excludeRegEx);
        }
      }
      $cache->set($cacheKey, $all, ClassScanner::TTL);
    }

    $event->classes = array_merge($event->classes, $all);
  });

};
