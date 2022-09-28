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
