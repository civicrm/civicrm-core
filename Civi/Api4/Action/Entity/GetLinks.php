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

namespace Civi\Api4\Action\Entity;

use Civi\Api4\Utils\CoreUtil;

/**
 * Get a list of FK links between entities.
 *
 * This action is deprecated; the API no longer uses these links to determine available joins.
 * @deprecated
 */
class GetLinks extends \Civi\Api4\Generic\BasicGetAction {

  public function getRecords() {
    \CRM_Core_Error::deprecatedWarning('APIv4 Entity::getLinks is deprecated.');
    $result = [];
    $schema = CoreUtil::getSchemaMap();
    foreach ($schema->getTables() as $table) {
      $entity = CoreUtil::getApiNameFromTableName($table->getName());
      // Since this is an api function, exclude tables that don't have an api
      if ($entity) {
        $item = [
          'entity' => $entity,
          'table' => $table->getName(),
          'links' => [],
        ];
        foreach ($table->getTableLinks() as $link) {
          if (!$link->isDeprecatedBy()) {
            $item['links'][] = $link->toArray();
          }
        }
        $result[] = $item;
      }
    }
    return $result;
  }

  public function fields() {
    return [
      [
        'name' => 'entity',
      ],
      [
        'name' => 'table',
      ],
      [
        'name' => 'links',
        'data_type' => 'Array',
      ],
    ];
  }

}
