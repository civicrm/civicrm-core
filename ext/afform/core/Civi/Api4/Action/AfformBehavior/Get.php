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
  public function getRecords():array {
    $entitiesToGet = $this->_itemsToGet('entity');

    $classes = ClassScanner::get(['interface' => BehaviorInterface::class]);
    /** @var \Civi\Afform\BehaviorInterface $behaviorClass */
    foreach ($classes as $behaviorClass) {
      $entities = $behaviorClass::getEntities();
      // Optimization
      if ($entitiesToGet && !array_intersect($entities, $entitiesToGet)) {
        continue;
      }
      $result[] = [
        'key' => $behaviorClass::getKey(),
        'title' => $behaviorClass::getTitle(),
        'description' => $behaviorClass::getDescription(),
        'entities' => $entities,
        'template' => $behaviorClass::getTemplate(),
        // Get modes for every supported entity
        'modes' => array_map([$behaviorClass, 'getModes'], array_combine($entities, $entities)),
        'default_mode' => $behaviorClass::getDefaultMode(),
      ];
    }
    return $result;
  }

}
