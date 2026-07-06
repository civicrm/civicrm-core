<?php

namespace Civi\Api4\Action\AfformBehavior;

use Civi\Afform\BehaviorInterface;
use Civi\Core\ClassScanner;
use CRM_Afform_ExtensionUtil as E;

/**
 * @inheritDoc
 * @package Civi\Api4\Action\Afform
 */
class Get extends \Civi\Api4\Generic\BasicGetAction {

  /**
   * @return array
   */
  public function getRecords(): array {
    $behaviors = \Civi::$statics[__METHOD__] ?? [];

    if (!$behaviors) {
      $classes = ClassScanner::get(['interface' => BehaviorInterface::class]);
      /** @var \Civi\Afform\BehaviorInterface $behaviorClass */
      foreach ($classes as $behaviorClass) {
        $entities = $behaviorClass::getEntities();
        // Optimization
        $behaviors[] = [
          'key' => $behaviorClass::getKey(),
          'attributes' => $behaviorClass::getAttributes(),
          'title' => $behaviorClass::getTitle(),
          'description' => $behaviorClass::getDescription(),
          'entities' => $entities,
          'template' => $behaviorClass::getTemplate(),
          // Get modes for every supported entity
          'modes' => array_map([$behaviorClass, 'getModes'], array_combine($entities, $entities)),
          'default_mode' => $behaviorClass::getDefaultMode(),
        ];
      }
      \Civi::$statics[__METHOD__] = $behaviors;
    }
    return $behaviors;
  }

}
