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

namespace Civi\Search;

/**
 * Class Admin
 * @package Civi\Search
 */
class Admin {

  /**
   * @return array
   */
  public static function getAdminSettings():array {
    return [
      'operators' => \CRM_Utils_Array::makeNonAssociative(self::getOperators()),
      'functions' => \CRM_Api4_Page_Api4Explorer::getSqlFunctions(),
    ];
  }

  /**
   * @return string[]
   */
  public static function getOperators():array {
    return [
      '=' => '=',
      '!=' => '≠',
      '>' => '>',
      '<' => '<',
      '>=' => '≥',
      '<=' => '≤',
      'CONTAINS' => ts('Contains'),
      'IN' => ts('Is In'),
      'NOT IN' => ts('Not In'),
      'LIKE' => ts('Is Like'),
      'NOT LIKE' => ts('Not Like'),
      'BETWEEN' => ts('Is Between'),
      'NOT BETWEEN' => ts('Not Between'),
      'IS NULL' => ts('Is Null'),
      'IS NOT NULL' => ts('Not Null'),
    ];
  }

  /**
   * Fetch all entities the current user has permission to `get`
   */
  public static function getSchema() {
    $schema = [];
    $entities = \Civi\Api4\Entity::get()
      ->addSelect('name', 'title', 'titlePlural', 'description', 'icon')
      ->addWhere('name', '!=', 'Entity')
      ->addOrderBy('titlePlural')
      ->setChain([
        'get' => ['$name', 'getActions', ['where' => [['name', '=', 'get']]], ['params']],
      ])->execute();
    $getFields = ['name', 'label', 'description', 'options', 'input_type', 'input_attrs', 'data_type', 'serialize', 'fk_entity'];
    foreach ($entities as $entity) {
      // Skip if entity doesn't have a 'get' action or the user doesn't have permission to use get
      if ($entity['get']) {
        $entity['fields'] = civicrm_api4($entity['name'], 'getFields', [
          'select' => $getFields,
          'where' => [['name', 'NOT IN', ['api_key', 'hash']]],
          'orderBy' => ['label'],
        ]);
        $params = $entity['get'][0];
        // Entity must support at least these params or it is too weird for search kit
        if (!array_diff(['select', 'where', 'orderBy', 'limit', 'offset'], array_keys($params))) {
          \CRM_Utils_Array::remove($params, 'checkPermissions', 'debug', 'chain', 'language', 'select', 'where', 'orderBy', 'limit', 'offset');
          unset($entity['get']);
          $schema[] = ['params' => array_keys($params)] + array_filter($entity);
        }
      }
    }
    return $schema;
  }

  /**
   * @return array
   */
  public static function getLinks($allowedEntities) {
    $results = [];
    $keys = array_flip(['alias', 'entity', 'joinType']);
    foreach (civicrm_api4('Entity', 'getLinks', ['where' => [['entity', 'IN', $allowedEntities]]], ['entity' => 'links']) as $entity => $links) {
      $entityLinks = [];
      foreach ($links as $link) {
        if (!empty($link['entity']) && in_array($link['entity'], $allowedEntities)) {
          // Use entity.alias as array key to avoid duplicates
          $entityLinks[$link['entity'] . $link['alias']] = array_intersect_key($link, $keys);
        }
      }
      $results[$entity] = array_values($entityLinks);
    }
    return array_filter($results);
  }

}
