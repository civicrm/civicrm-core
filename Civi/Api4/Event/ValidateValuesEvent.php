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

use Civi\API\Event\RequestTrait;
use Civi\Core\Event\GenericHookEvent;

/**
 * The ValidateValuesEvent ('civi.api4.validate') is emitted when creating or saving an entire record via APIv4.
 * It is emitted once for every record is updated.
 *
 * Example #1: Walk each record and validate some fields
 *
 * function(ValidateValuesEvent $e) {
 *   if ($e->entity !== 'Foozball') return;
 *   foreach ($e->records as $r => $record) {
 *     if (strtotime($record['start_time']) < CRM_Utils_Time::time()) {
 *       $e->addError($r, 'start_time', 'past', ts('Start time has already passed.'));
 *     }
 *     if ($record['length'] * $record['width'] * $record['height'] > VOLUME_LIMIT) {
 *       $e->addError($r, ['length', 'width', 'height'], 'excessive_volume', ts('The record is too big.'));
 *     }
 *   }
 * }
 *
 * Example #2: Prohibit recording `Contribution` records on `Student` contacts.
 *
 * function(ValidateValuesEvent $e) {
 *   if ($e->entity !== 'Contribution') return;
 *   $contactSubTypes = CRM_Utils_SQL_Select::from('civicrm_contact')
 *     ->where('id IN (#ids)', ['ids' => array_column($e->records, 'contact_id')])
 *     ->select('id, contact_sub_type')
 *     ->execute()->fetchMap('id', 'contact_sub_type');
 *   foreach ($e->records as $r => $record) {
 *     if ($contactSubTypes[$record['contact_id']] === 'Student') {
 *       $e->addError($r, 'contact_id', 'student_prohibited', ts('Donations from student records are strictly prohibited.'));
 *     }
 *   }
 * }
 */
class ValidateValuesEvent extends GenericHookEvent {

  use RequestTrait;

  /**
   * List of updated records.
   *
   * The list of `$records` reflects only the list of new values assigned
   * by this action. It may or may not correspond to an existing row in the database.
   * It is similar to the `$records` list used by `save()`.
   *
   * @var array|\CRM_Utils_LazyArray
   * @see \Civi\Api4\Generic\AbstractSaveAction::$records
   */
  public $records;

  /**
   * Detailed, side-by-side comparison of old and new values.
   *
   * This requires loading the list of old values from the database. Consequently,
   * reading `$diffs` is more expensive than reading `$records`, so you should only use it if
   * really necessary.
   *
   * The list of $diffs may be important if you are enforcing a rule that involves
   * multiple fields. (Ex: "Validate that the state_id and country_id match.")
   *
   * When possible, $records and $diffs will have the same number of items (with corresponding
   * keys). However, in the case of a batch `update()`, the list of diffs will be longer.
   *
   * @var array|\CRM_Utils_LazyArray
   *   Each item is a record of the form ['old' => $fieldValues, 'new' => $fieldValues]
   */
  public $diffs;

  /**
   * List of error messages.
   *
   * @var array
   *   Array(string $errorName => string $errorMessage)
   *   Note:
   */
  public $errors = [];

  /**
   * ValidateValuesEvent constructor.
   *
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   * @param array|\CRM_Utils_LazyArray $records
   *   List of updates (akin to SaveAction::$records).
   * @param array|\CRM_Utils_LazyArray $diffs
   *   List of differences (comparing old values and new values).
   */
  public function __construct($apiRequest, $records, $diffs) {
    $this->setApiRequest($apiRequest);
    $this->records = $records;
    $this->diffs = $diffs;
    $this->errors = [];
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->getApiRequest(), $this->records, &$this->errors];
  }

  /**
   * Add an error.
   *
   * @param string|int $recordKey
   *   The validator may work with multiple records. This should identify the specific record.
   *   Each record is identified by its offset (`$records[$recordKey] === [...the record...]`).
   * @param string|array $field
   *   The name of the field which has an error.
   *   If the error is multi-field (e.g. mismatched password-confirmation), then use an array.
   *   If the error is independent of any field, then use [].
   * @param string $name
   * @param string|null $message
   * @return $this
   */
  public function addError($recordKey, $field, string $name, ?string $message = NULL): self {
    $this->errors[] = [
      'record' => $recordKey,
      'fields' => (array) $field,
      'name' => $name,
      'message' => $message ?: ts('Error code (%1)', [1 => $name]),
    ];
    return $this;
  }

  /**
   * Convert the list of errors an exception.
   *
   * @return \CRM_Core_Exception
   */
  public function toException() {
    // We should probably have a better way to report the errors in a structured/list format.
    return new \CRM_Core_Exception(ts('Found %1 error(s) in submitted %2 record(s) of type "%3": %4', [
      1 => count($this->errors),
      2 => count(array_unique(array_column($this->errors, 'record'))),
      3 => $this->getEntityName(),
      4 => implode(', ', array_column($this->errors, 'message')),
    ]));
  }

}
