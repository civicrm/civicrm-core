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

namespace Civi\Api4\Generic;

use Civi\Api4\Event\ValidateValuesEvent;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Utils\CoreUtil;

/**
 * Create or update one or more $ENTITIES.
 *
 * Pass an array of one or more $ENTITY to save in the `records` param.
 *
 * If creating more than one $ENTITY with similar values, use the `defaults` param.
 *
 * Set `reload` if you need the api to return complete records for each saved $ENTITY
 * (including values that were unchanged in from updated $ENTITIES).
 *
 * @method $this setRecords(array $records) Set array of records to be saved.
 * @method array getRecords()
 * @method $this setDefaults(array $defaults) Array of defaults.
 * @method array getDefaults()
 * @method $this setReload(bool $reload) Specify whether complete objects will be returned after saving.
 * @method bool getReload()
 * @method $this setMatch(array $match) Specify fields to match for update.
 * @method bool getMatch()
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractSaveAction extends AbstractAction {
  use Traits\MatchParamTrait;

  /**
   * Array of $ENTITIES to save.
   *
   * Should be in the same format as returned by `Get`.
   *
   * @var array
   * @required
   */
  protected $records = [];

  /**
   * Array of default values.
   *
   * These defaults will be merged into every $ENTITY in `records` before saving.
   * Values set in `records` will override these defaults if set in both places,
   * but updating existing $ENTITIES will overwrite current values with these defaults.
   *
   * @var array
   */
  protected $defaults = [];

  /**
   * Reload $ENTITIES after saving.
   *
   * By default this action typically returns partial records containing only the fields
   * that were updated. Set `reload` to `true` to do an additional lookup after saving
   * to return complete values for every $ENTITY.
   *
   * @var bool
   */
  protected $reload = FALSE;

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function validateValues() {
    $idField = CoreUtil::getIdFieldName($this->getEntityName());
    // FIXME: There should be a protocol to report a full list of errors... Perhaps a subclass of CRM_Core_Exception?
    $unmatched = [];
    foreach ($this->records as $record) {
      if (empty($record[$idField])) {
        $unmatched = array_unique(array_merge($unmatched, $this->checkRequiredFields($record)));
      }
    }
    if ($unmatched) {
      throw new \CRM_Core_Exception("Mandatory values missing from Api4 {$this->getEntityName()}::{$this->getActionName()}: " . implode(", ", $unmatched), "mandatory_missing", ["fields" => $unmatched]);
    }

    if ($this->checkPermissions) {
      foreach ($this->records as $record) {
        $action = empty($record[$idField]) ? 'create' : 'update';
        if (!CoreUtil::checkAccessDelegated($this->getEntityName(), $action, $record, \CRM_Core_Session::getLoggedInContactID() ?: 0)) {
          throw new UnauthorizedException("ACL check failed");
        }
      }
    }

    $e = new ValidateValuesEvent($this, $this->records, new \CRM_Utils_LazyArray(function() use ($idField) {
      $existingIds = array_column($this->records, $idField);
      $existing = civicrm_api4($this->getEntityName(), 'get', [
        'checkPermissions' => $this->checkPermissions,
        'where' => [[$idField, 'IN', $existingIds]],
      ], $idField);

      $result = [];
      foreach ($this->records as $k => $new) {
        $old = isset($new[$idField]) ? $existing[$new[$idField]] : NULL;
        $result[$k] = ['old' => $old, 'new' => $new];
      }
      return $result;
    }));
    \Civi::dispatcher()->dispatch('civi.api4.validate', $e);
    if (!empty($e->errors)) {
      throw $e->toException();
    }
  }

  /**
   * @return string
   * @deprecated
   */
  protected function getIdField() {
    return CoreUtil::getInfoItem($this->getEntityName(), 'primary_key')[0];
  }

  /**
   * Add one or more records to be saved.
   * @param array ...$records
   * @return $this
   */
  public function addRecord(array ...$records) {
    $this->records = array_merge($this->records, $records);
    return $this;
  }

  /**
   * Set default value for a field.
   * @param string $fieldName
   * @param mixed $defaultValue
   * @return $this
   */
  public function addDefault(string $fieldName, $defaultValue) {
    $this->defaults[$fieldName] = $defaultValue;
    return $this;
  }

}
