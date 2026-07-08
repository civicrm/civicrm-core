<?php

namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Generic\AbstractAction;
use MJS\TopSort\Implementations\FixedArraySort;

/**
 * This event gathers dependencies between entities on the form.
 * This is used to sort entities before processing, so dependencies
 * are saved first and entity references can be resolved
 */
class AfformEntitySortEvent extends AfformBaseEvent {

  protected array $entityValues;

  /**
   * @var string[][]
   *
   * [
   *   entityName => [dependentEntity1, dependentEntity2],
   *   ...
   * ]
   *
   * internal storage for dependencies, which will then be passed to the top sort
   */
  protected array $elements = [];

  public function __construct(array $afform, FormDataModel $formDataModel, AbstractAction $apiRequest, array $entityValues = []) {
    parent::__construct($afform, $formDataModel, $apiRequest);
    $this->entityValues = $entityValues;

    // add all entity names as nodes (with no dependencies at this stage)
    // Q: should we add dependencies based on data values here?
    foreach ($formDataModel->getEntities() as $entity => $details) {
      if (empty($details['type'])) {
        // this filters out things like "extra" which aren't really entities
        // but are returned by `getEntities`
        // TODO: filter out upstream?
        continue;
      }
      $this->addEntity($entity);
    }

    // add entity ref dependencies from values, if any
    $this->addEntityRefDependencies();
  }

  /**
   * @param string $entityName
   *   add an entity to the list of nodes
   */
  public function addEntity(string $entityName): void {
    $this->elements[$entityName] ??= [];
  }

  /**
   * @param string $dependentEntity
   * @param string $dependsOnEntity
   */
  public function addDependency(string $dependentEntity, string $dependsOnEntity): void {
    // ensure the node exists
    $this->addEntity($dependentEntity);
    // add the dependency to the list of entities
    $this->elements[$dependentEntity][] = $dependsOnEntity;
  }

  /**
   * Add dependencies between Entities based on EntityRef values in the
   * submission
   *
   * TODO: this just matches string literals. If you have a text field and
   * enter "Individual1" that could get interpreted as a reference. It might
   * be good to check the field type
   */
  protected function addEntityRefDependencies(): void {
    $formEntities = array_keys($this->getFormDataModel()->getEntities());

    foreach ($this->entityValues as $entityName => $records) {
      foreach ($records as $record) {
        foreach ($record['fields'] ?? [] as $fieldValue) {
          foreach ((array) $fieldValue as $value) {
            if (in_array($value, $formEntities, TRUE)) {
              $this->addDependency($entityName, $value);
            }
          }
        }
      }
    }
  }

  /**
   * @return string[] list of entities sorted by dependencies
   * @throws \MJS\TopSort\CircularDependencyException
   * @throws \MJS\TopSort\ElementNotFoundException
   */
  public function getSorted(): array {
    $sorter = new FixedArraySort();
    foreach ($this->elements as $element => $dependencies) {
      $sorter->add($element, $dependencies);
    }
    return $sorter->sort();
  }

}
