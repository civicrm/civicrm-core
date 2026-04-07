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

namespace Civi\Api4\Generic\Traits;

use Civi\Api4\Utils\CoreUtil;

/**
 * @method $this setMatch(array $match) Specify fields to match for update.
 * @method bool getMatch()
 * @package Civi\Api4\Generic
 */
trait MatchParamTrait {

  /**
   * Specify fields to match for update.
   *
   * The API will perform an update if an existing $ENTITY matches all specified fields.
   *
   * Note: the fields named in this param should be without any options suffix (e.g. `my_field` not `my_field:name`).
   * Any options suffixes in the $records will be resolved by the api prior to matching.
   *
   * @var array
   * @optionsCallback getMatchFields
   */
  protected $match = [];

  /**
   * Find existing record based on $this->match param
   *
   * @param $record
   * @return int
   *   Returns number of existing records (1 or 0)
   */
  protected function matchExisting(&$record): int {
    $primaryKey = CoreUtil::getIdFieldName($this->getEntityName());
    if (empty($record[$primaryKey]) && !empty($this->match)) {
      $where = [];
      foreach ($record as $key => $val) {
        if (in_array($key, $this->match, TRUE)) {
          if ($val === '' || is_null($val)) {
            // If we want to match empty string we have to match on NULL/''
            $where[] = [$key, 'IS EMPTY'];
          }
          else {
            $where[] = [$key, '=', $val];
          }
        }
      }
      if (count($where) === count($this->match)) {
        $existing = civicrm_api4($this->getEntityName(), 'get', [
          'select' => [$primaryKey],
          'where' => $where,
          'checkPermissions' => $this->checkPermissions,
          'limit' => 2,
        ]);
        if ($existing->count() === 1) {
          $record[$primaryKey] = $existing->first()[$primaryKey];
        }
      }
    }
    return empty($record[$primaryKey]) ? 0 : 1;
  }

  /**
   * Options callback for $this->match
   * @return array
   */
  protected function getMatchFields() {
    return (array) civicrm_api4($this->getEntityName(), 'getFields', [
      'checkPermissions' => FALSE,
      'action' => 'get',
      'where' => [
        ['type', 'IN', ['Field', 'Custom']],
        ['readonly', '!=', TRUE],
      ],
    ], ['name']);
  }

}
