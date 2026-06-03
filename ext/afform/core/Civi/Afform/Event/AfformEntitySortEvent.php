<?php

namespace Civi\Afform\Event;

use MJS\TopSort\Implementations\FixedArraySort;

/**
 * This event allows listeners to declare that entities depend on others.
 * These dependencies change the order in which entities are resolved.
 */
class AfformEntitySortEvent extends AfformBaseEvent {

  private $dependencies = [];

  private $entityValues = [];

  private FixedArraySort $sorter;

  /**
   * @param string $dependentEntity
   * @param string $dependsOnEntity
   */
  public function addDependency(string $dependentEntity, string $dependsOnEntity): void {
    $this->dependencies[$dependentEntity][$dependsOnEntity] = $dependsOnEntity;
  }

  public function setEntityValues(array $entityValues): void {
    $this->entityValues = $entityValues;
  }

  /**
   * Returns list of entity names that have a defined type.
   *
   * @return array
   */
  private function getFormEntities(): array {
    $entities = [];
    foreach ($this->getFormDataModel()->getEntities() as $name => $entity) {
      if (!empty($entity['type'])) {
        $entities[] = $name;
      }
    }
    return $entities;
  }

  /**
   * Returns entity names sorted by their dependencies
   *
   * @return array
   */
  public function getSortedEntitiesPrefill(): array {
    $sorter = new FixedArraySort();
    $formEntities = $this->getFormEntities();
    foreach ($formEntities as $entityName) {
      // Add all dependencies that are the valid name of another entity
      $dependencies = array_intersect($this->dependencies[$entityName] ?? [], $formEntities);
      $sorter->add($entityName, $dependencies);
    }
    return $sorter->sort();
  }

  /**
   * Returns a list of entity names in order of when they should be processed,
   * so that an entity being referenced is saved before the entity referencing it.
   *
   */
  public function getEntityDependenciesForSubmit(): void {
    $sorter = new FixedArraySort();
    $formEntities = $this->getFormEntities();
    $entityValues = $this->entityValues;

    foreach ($formEntities as $entityName) {
      $references = [];
      foreach ($entityValues[$entityName] ?? [] as $record) {
        foreach ($record['fields'] ?? [] as $fieldName => $fieldValue) {
          foreach ((array) $fieldValue as $value) {
            if (in_array($value, $formEntities, TRUE) && $value !== $entityName) {
              $references[$value] = $value;
            }
          }
        }
      }
      $sorter->add($entityName, $references);
    }

    // Return the list of entities ordered by weight
    $this->sorter = $sorter;
  }

  /**
   * @throws \MJS\TopSort\CircularDependencyException
   * @throws \MJS\TopSort\ElementNotFoundException
   */
  public function sort() {
    return $this->sorter->sort();
  }

}
