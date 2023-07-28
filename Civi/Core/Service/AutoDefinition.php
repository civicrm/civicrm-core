<?php

namespace Civi\Core\Service;

use Civi\Api4\Utils\ReflectionUtils;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AutoDefinition {

  /**
   * Oddballs - AutoDefinition can apply a tag to a third-party class/interface. But it's better
   * for classes/interfaces to declare `serviceTags` for themselves.
   *
   * @var string[]
   */
  protected static $forceServiceTags = [
    'Symfony\Component\EventDispatcher\EventSubscriberInterface' => 'event_subscriber',
  ];

  /**
   * Identify any/all service-definitions for the given class.
   *
   * If the class defines any static factory methods, then there may be multiple definitions.
   *
   * @param string $className
   *   The name of the class to scan. Look for `@inject` and `@service` annotations.
   * @return \Symfony\Component\DependencyInjection\Definition[]
   *   Ex: ['my.service' => new Definition('My\Class')]
   */
  public static function scan(string $className): array {
    $class = new \ReflectionClass($className);
    $result = [];

    $classDoc = ReflectionUtils::parseDocBlock($class->getDocComment());
    // AutoSubscriber is an internal service by default
    if (is_a($className, AutoSubscriber::class, TRUE)) {
      $classDoc += ['service' => TRUE, 'internal' => TRUE];
    }
    if (!empty($classDoc['service'])) {
      $serviceName = static::pickName($classDoc, $class->getName());
      $def = static::createBaseline($class, $classDoc);
      self::applyConstructor($def, $class);
      $result[$serviceName] = $def;
    }

    foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC) as $method) {
      /** @var \ReflectionMethod $method */
      $methodDoc = ReflectionUtils::parseDocBlock($method->getDocComment());
      if (!empty($methodDoc['service'])) {
        $serviceName = static::pickName($methodDoc, $class->getName() . '::' . $method->getName());
        $returnClass = isset($methodDoc['return'][0]) ? new \ReflectionClass($methodDoc['return'][0]) : $class;
        $def = static::createBaseline($returnClass, $methodDoc);
        $def->setFactory($class->getName() . '::' . $method->getName());
        $def->setArguments(static::toReferences($methodDoc['inject'] ?? ''));
        $result[$serviceName] = $def;
      }
    }

    if (count($result) === 0) {
      error_log("WARNING: Class {$class->getName()} was expected to have a service definition, but it did not. Perhaps it needs service name.");
    }

    return $result;
  }

  /**
   * Create a basic definition for an unnamed service.
   *
   * @param string $className
   *   The name of the class to scan. Look for `@inject` and `@service` annotations.
   * @return \Symfony\Component\DependencyInjection\Definition
   */
  public static function create(string $className): Definition {
    $class = new \ReflectionClass($className);
    $classDoc = ReflectionUtils::parseDocBlock($class->getDocComment());
    $def = static::createBaseline($class, $classDoc);
    static::applyConstructor($def, $class);
    return $def;
  }

  protected static function pickName(array $docBlock, string $internalDefault): string {
    if (is_string($docBlock['service'])) {
      return $docBlock['service'];
    }
    if (!empty($docBlock['internal']) && $internalDefault) {
      return $internalDefault;
    }
    throw new \RuntimeException("Error: Failed to determine service name ($internalDefault). Please specify '@service NAME' or '@internal'.");
  }

  protected static function createBaseline(\ReflectionClass $class, ?array $docBlock = []): Definition {
    $def = new Definition($class->getName());
    $def->setPublic(TRUE);
    self::applyTags($def, $class, $docBlock);
    self::applyObjectProperties($def, $class);
    self::applyObjectMethods($def, $class);
    return $def;
  }

  protected static function toReferences(string $injectExpr): array {
    return array_map(
      function (string $part) {
        return new Reference($part);
      },
      static::splitSymbols($injectExpr)
    );
  }

  protected static function splitSymbols(string $expr): array {
    if ($expr === '') {
      return [];
    }
    $extraTags = explode(',', $expr);
    return array_map('trim', $extraTags);
  }

  /**
   * @param \Symfony\Component\DependencyInjection\Definition $def
   * @param \ReflectionClass $class
   * @param array $docBlock
   */
  protected static function applyTags(Definition $def, \ReflectionClass $class, array $docBlock): void {
    if (!empty($docBlock['internal'])) {
      $def->addTag('internal');
    }

    $tags = static::findTags($class, $docBlock, FALSE);
    foreach ($tags as $tag) {
      $def->addTag($tag);
    }
  }

  /**
   * Find all `serviceTags` annotations that apply to a class -- either
   * directly or indirectly (via interface, trait, or parent-class).
   *
   * @param \ReflectionClass $class
   * @param array|null $docBlock
   * @param bool $isTransitiveLookup
   * @return array|mixed
   */
  public static function findTags(\ReflectionClass $class, ?array $docBlock, bool $isTransitiveLookup) {
    $className = $class->getName();
    $cache = &\Civi::$statics[__CLASS__]['tagidx'];
    if (isset($cache[$className])) {
      return $cache[$className];
    }

    $docBlock = $docBlock ?: ReflectionUtils::parseDocBlock($class->getDocComment());
    $result = isset($docBlock['serviceTags']) ? static::splitSymbols($docBlock['serviceTags']) : [];
    if (isset(static::$forceServiceTags[$className])) {
      $result[] = static::$forceServiceTags[$className];
    }
    $parents = array_merge($class->getInterfaces(), $class->getTraits(), [$class->getParentClass()]);
    foreach ($parents as $parent) {
      if ($parent) {
        $result = array_merge($result, static::findTags($parent, NULL, TRUE));
        // Aside: The recursion might theoretically visit an interface multiple times, but ancestral
        // lookups are cached... so not really...
      }
    }
    $result = array_unique($result);

    // We cache info about common/re-usable classes (interfaces, traits, parents).
    if ($isTransitiveLookup) {
      $cache[$className] = $result;
    }
    return $result;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\Definition $def
   * @param \ReflectionClass $class
   */
  protected static function applyConstructor(Definition $def, \ReflectionClass $class): void {
    if ($construct = $class->getConstructor()) {
      $constructAnno = ReflectionUtils::parseDocBlock($construct->getDocComment() ?? '');
      if (!empty($constructAnno['inject'])) {
        $def->setArguments(static::toReferences($constructAnno['inject']));
      }
    }
  }

  /**
   * Scan for any methods with `@inject`. They should be invoked via `$def->addMethodCall()`.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $def
   * @param \ReflectionClass $class
   */
  protected static function applyObjectMethods(Definition $def, \ReflectionClass $class): void {
    foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
      /** @var \ReflectionMethod $method */
      if ($method->isStatic()) {
        continue;
      }

      $anno = ReflectionUtils::parseDocBlock($method->getDocComment());
      if (!empty($anno['inject'])) {
        $def->addMethodCall($method->getName(), static::toReferences($anno['inject']));
      }
    }
  }

  /**
   * Scan for any properties with `@inject`. They should be configured via `$def->setProperty()`
   * or via `injectPrivateProperty()`.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $def
   * @param \ReflectionClass $class
   * @throws \Exception
   */
  protected static function applyObjectProperties(Definition $def, \ReflectionClass $class): void {
    foreach ($class->getProperties() as $property) {
      /** @var \ReflectionProperty $property */
      if ($property->isStatic()) {
        continue;
      }

      $propDoc = ReflectionUtils::getCodeDocs($property);
      if (!empty($propDoc['inject'])) {
        if ($propDoc['inject'] === TRUE) {
          $propDoc['inject'] = $property->getName();
        }
        if ($property->isPublic()) {
          $def->setProperty($property->getName(), new Reference($propDoc['inject']));
        }
        elseif ($class->hasMethod('injectPrivateProperty')) {
          $def->addMethodCall('injectPrivateProperty', [$property->getName(), new Reference($propDoc['inject'])]);
        }
        else {
          throw new \Exception(sprintf('Property %s::$%s is marked private. To inject services into private properties, you must implement method "injectPrivateProperty($key, $value)".',
            $class->getName(), $property->getName()
          ));
        }
      }
    }
  }

}
