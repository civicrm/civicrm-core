<?php

/**
 * Scan for files which implement common Civi-PHP interfaces.
 *
 * Specifically, this listens to `hook_scanClasses` and reports any classes with Civi-related
 * interfaces or attributes. For example:
 *
 *   - #[CiviEventListener]
 *   - \Civi\Core\HookInterface
 *   - \Civi\Core\Service\AutoServiceInterface
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
 * @since 6.21
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
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
      ClassScanner::scanFolders($all, $baseDir, 'Civi', '\\');
      $cache->set($cacheKey, $all, ClassScanner::TTL);
    }

    $event->classes = array_merge($event->classes, $all);
  });

  // Polyfill #[CiviEventListener] on CiviCRM < 6.21
  static $hasNativeSupport = NULL;
  if ($hasNativeSupport === NULL) {
    $hasNativeSupport = class_exists('Civi\Core\Attribute\CiviEventListener');
    if (!$hasNativeSupport) {
      // phpcs:ignore Drupal.Functions.DiscouragedFunctions.Discouraged
      eval('namespace Civi\Core\Attribute {
        #[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
        class CiviEventListener {
          public function __construct(
            public string $event,
            public int $priority = 0
          ) {}
        }
      }');
    }
  }

  if (!$hasNativeSupport) {
    Civi::dispatcher()->addListener('hook_civicrm_container', function ($event) use ($mixInfo) {
      if (!$mixInfo->isActive()) {
        return;
      }

      $container = ($event instanceof \Symfony\Component\DependencyInjection\ContainerBuilder) ? $event : ($event->container ?? NULL);
      if (!$container) {
        return;
      }
      $dispatcher = $container->getDefinition('dispatcher');

      $baseDir = CRM_Utils_File::addTrailingSlash($mixInfo->getPath());
      $allClasses = [];
      foreach (['CRM' => '_', 'Civi' => '\\'] as $dir => $delim) {
        foreach ((array) glob($baseDir . $dir) as $d) {
          foreach (CRM_Utils_File::findFiles($d, '*.php') as $file) {
            if (preg_match(ClassScanner::CIVI_CLASS_FILE_REGEX, basename($file))) {
              $rel = CRM_Utils_File::relativize(str_replace(DIRECTORY_SEPARATOR, '/', $file), $baseDir);
              $class = str_replace('/', $delim, substr($rel, 0, -4));
              if (class_exists($class)) {
                $allClasses[] = $class;
              }
            }
          }
        }
      }

      $definedClasses = [];
      foreach ($container->getDefinitions() as $id => $definition) {
        $definedClasses[ltrim((string) $definition->getClass(), '\\')] = $id;
      }

      foreach ($allClasses as $class) {
        $refClass = new \ReflectionClass($class);
        if ($refClass->isAbstract() || $refClass->isTrait()) {
          continue;
        }

        $listenerMap = [];
        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
          foreach ($method->getAttributes('Civi\Core\Attribute\CiviEventListener', \ReflectionAttribute::IS_INSTANCEOF) as $attr) {
            $listener = $attr->newInstance();
            $eventName = $listener->event;
            if (str_contains($eventName, '{self}')) {
              $self = \CRM_Core_DAO_AllCoreTables::getEntityNameForClass($class);
              if ($self !== NULL) {
                $eventName = str_replace('{self}', $self, $eventName);
              }
            }
            $listenerMap[$eventName][] = [$method->getName(), $listener->priority];
          }
        }

        if ($listenerMap) {
          if (is_subclass_of($class, \CRM_Core_DAO::class)) {
            $dispatcher->addMethodCall('addListenerMap', [$class, $listenerMap]);
          }
          else {
            $serviceId = $definedClasses[$class] ?? $class;
            if (!isset($definedClasses[$class])) {
              $definedClasses[$class] = $serviceId;
              $container->setDefinition($serviceId, \Civi\Core\Service\AutoDefinition::create($class));
            }
            $dispatcher->addMethodCall('addSubscriberServiceMap', [$serviceId, $listenerMap]);
          }
        }
      }
    });
  }
};
