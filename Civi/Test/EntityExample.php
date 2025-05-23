<?php

namespace Civi\Test;

/**
 * Helper class for defining entity examples.
 *
 * By convention, you should name this class relative to the target workflow,
 * as in:
 *
 * - Entity Name: ContributionRecur
 * - Example Data: Civi\Test\ExampleData\ContributionRecur\Euro5990
 * - Example Name: entity/ContributionRecur/Euro5900
 */
abstract class EntityExample implements ExampleDataInterface {

  /**
   * @var string
   */
  protected $entityName;

  /**
   * @var string
   */
  protected $exName;

  /**
   * Get the name of the example.
   *
   * @return string
   */
  protected function getExampleName(): string {
    return $this->exName;
  }

  public function __construct() {
    if (!preg_match(';^(.*)[_\\\]([a-zA-Z0-9]+)[_\\\]([a-zA-Z0-9]+)$;', static::class, $m)) {
      throw new \RuntimeException("Failed to parse class: " . static::class);
    }
    $this->entityName = $m[2];
    $this->exName = $m[3];
  }

  protected function dao(): string {
    return \CRM_Core_DAO_AllCoreTables::getDAONameForEntity($this->entityName);
  }

  protected function bao(): string {
    return \CRM_Core_DAO_AllCoreTables::getBAOClassName($this->getDAO());
  }

}
