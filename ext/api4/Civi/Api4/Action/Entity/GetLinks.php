<?php

namespace Civi\Api4\Action\Entity;

use \CRM_Core_DAO_AllCoreTables as AllCoreTables;

/**
 * Get a list of FK links between entities
 */
class GetLinks extends \Civi\Api4\Generic\BasicGetAction {

  public function getRecords() {
    $result = [];
    /** @var \Civi\Api4\Service\Schema\SchemaMap $schema */
    $schema = \Civi::container()->get('schema_map');
    foreach ($schema->getTables() as $table) {
      $entity = AllCoreTables::getBriefName(AllCoreTables::getClassForTable($table->getName()));
      // Since this is an api function, exclude tables that don't have an api
      if (class_exists('\Civi\Api4\\' . $entity)) {
        $item = [
          'entity' => $entity,
          'table' => $table->getName(),
          'links' => [],
        ];
        foreach ($table->getTableLinks() as $link) {
          $link = $link->toArray();
          $link['entity'] = AllCoreTables::getBriefName(AllCoreTables::getClassForTable($link['targetTable']));
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
