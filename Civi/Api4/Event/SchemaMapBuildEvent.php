<?php

namespace Civi\Api4\Event;

use Civi\Api4\Service\Schema\SchemaMap;
use Symfony\Component\EventDispatcher\Event as BaseEvent;

class SchemaMapBuildEvent extends BaseEvent {
  /**
   * @var \Civi\Api4\Service\Schema\SchemaMap
   */
  protected $schemaMap;

  /**
   * @param \Civi\Api4\Service\Schema\SchemaMap $schemaMap
   */
  public function __construct(SchemaMap $schemaMap) {
    $this->schemaMap = $schemaMap;
  }

  /**
   * @return \Civi\Api4\Service\Schema\SchemaMap
   */
  public function getSchemaMap() {
    return $this->schemaMap;
  }

  /**
   * @param \Civi\Api4\Service\Schema\SchemaMap $schemaMap
   *
   * @return $this
   */
  public function setSchemaMap($schemaMap) {
    $this->schemaMap = $schemaMap;

    return $this;
  }

}
