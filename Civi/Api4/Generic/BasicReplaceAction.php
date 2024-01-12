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

/**
 * Replaces an existing set of $ENTITIES with a new one.
 *
 * This will select a group of existing $ENTITIES based on the `where` parameter.
 * Each will be compared with the $ENTITIES passed in as `records`:
 *
 *  - $ENTITIES in `records` that don't already exist will be created.
 *  - Existing $ENTITIES that are included in `records` will be updated.
 *  - Existing $ENTITIES that are omitted from `records` will be deleted.
 *
 * @method $this setRecords(array $records) Set array of records.
 * @method array getRecords()
 * @method $this setDefaults(array $defaults) Set array of defaults.
 * @method array getDefaults()
 * @method $this setReload(bool $reload) Specify whether complete objects will be returned after saving.
 * @method bool getReload()
 */
class BasicReplaceAction extends AbstractBatchAction {
  use Traits\MatchParamTrait;

  /**
   * Array of $ENTITY records.
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
   * **Note:** Values from the `where` clause that use the `=` operator are _also_ treated as default values;
   * those do not need to be repeated here.
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
   * @return \Civi\Api4\Result\ReplaceResult
   */
  public function execute() {
    return parent::execute();
  }

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $items = $this->getBatchRecords();

    // Copy defaults from where clause if the operator is =
    foreach ($this->where as $clause) {
      if (is_array($clause) && $clause[1] === '=') {
        $this->defaults[$clause[0]] = $clause[2];
      }
    }

    // Merge in defaults and perform non-id matching if match field(s) are specified
    foreach ($this->records as &$record) {
      $record += $this->defaults;
      $this->formatWriteValues($record);
      $this->matchExisting($record);
    }

    $idField = $this->getSelect()[0];
    $toDelete = array_diff_key(array_column($items, NULL, $idField), array_flip(array_column($this->records, $idField)));

    $saveAction = \Civi\API\Request::create($this->getEntityName(), 'save', ['version' => 4]);
    $saveAction
      ->setCheckPermissions($this->getCheckPermissions())
      ->setReload($this->reload)
      ->setRecords($this->records);
    $result->exchangeArray((array) $saveAction->execute());

    if ($toDelete) {
      $result->deleted = (array) civicrm_api4($this->getEntityName(), 'delete', [
        'where' => [[$idField, 'IN', array_keys($toDelete)]],
        'checkPermissions' => $this->getCheckPermissions(),
      ]);
    }
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

  /**
   * Add one or more records
   * @param array ...$records
   * @return $this
   */
  public function addRecord(array ...$records) {
    $this->records = array_merge($this->records, $records);
    return $this;
  }

}
