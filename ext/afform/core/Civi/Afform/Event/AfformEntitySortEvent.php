<?php

namespace Civi\Afform\Event;

use MJS\TopSort\Implementations\FixedArraySort;

/**
 * This event allows listeners to declare that entities depend on others.
 * These dependencies change the order in which entities are resolved.
 */
class AfformEntitySortEvent extends AfformBaseEvent {

  private $dependencies = [];

  /**
   * @param string $dependentEntity
   * @param string $dependsOnEntity
   */
  public function addDependency(string $dependentEntity, string $dependsOnEntity): void {
    $this->dependencies[$dependentEntity][$dependsOnEntity] = $dependsOnEntity;
  }

  /**
   * Returns entity names sorted by their dependencies
   *
   * @return array
   */
  public function getSortedEnties(): array {
    $sorter = new FixedArraySort();
    $formEntities = array_keys($this->getFormDataModel()->getEntities());
    foreach ($formEntities as $entityName) {
      // Add all dependencies that are the valid name of another entity
      $dependencies = array_intersect($this->dependencies[$entityName] ?? [], $formEntities);
      $sorter->add($entityName, $dependencies);
    }
    return $sorter->sort();
  }

}
