<?php

namespace Civi\Api4\Action\Entity;

use Civi\Api4\Utils\CoreUtil;

/**
 * Get a list of FK links between entities
 */
class GetLinks extends \Civi\Api4\Generic\BasicGetAction {

  public function getRecords() {
    $result = [];
    /** @var \Civi\Api4\Service\Schema\SchemaMap $schema */
    $schema = \Civi::container()->get('schema_map');
    foreach ($schema->getTables() as $table) {
      $entity = CoreUtil::getApiNameFromTableName($table->getName());
      // Since this is an api function, exclude tables that don't have an api
      if (class_exists('\Civi\Api4\\' . $entity)) {
        $item = [
          'entity' => $entity,
          'table' => $table->getName(),
          'links' => [],
        ];
        foreach ($table->getTableLinks() as $link) {
          $link = $link->toArray();
          $link['entity'] = CoreUtil::getApiNameFromTableName($link['targetTable']);
          $item['links'][] = $link;
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
