<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Event;

use Civi\Api4\Service\Schema\SchemaMap;
use Civi\Core\Event\GenericHookEvent as BaseEvent;

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
