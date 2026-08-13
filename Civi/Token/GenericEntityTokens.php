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

namespace Civi\Token;

class GenericEntityTokens extends \CRM_Core_EntityTokens {

  /**
   * @var string
   */
  private string $apiEntityName;

  /**
   * Class constructor.
   */
  public function __construct($entity) {
    $this->apiEntityName = $entity;
    parent::__construct();
  }

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return $this->apiEntityName;
  }

  /**
   * Get entity fields that should be exposed as tokens.
   *
   * @return string[]
   *
   */
  protected function getExposedFields(): array {
    $return = [];
    foreach ($this->getFieldMetadata() as $field) {
      if (!in_array($field['name'], $this->getSkippedFields(), TRUE)) {
        if ($field['type'] !== 'Custom' && !in_array('token', $field['usage'] ?? [], TRUE)) {
          continue;
        }
        $return[] = $field['name'];
      }
    }
    return $return;
  }

}
