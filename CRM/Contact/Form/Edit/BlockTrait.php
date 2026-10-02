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


/**
 * Common helper trait for location block forms and traits.
 *
 * Provides shared methods for querying and saving multi-instance contact
 * blocks (Email, Phone, IM, OpenID, Website).
 *
 * @internal not supported for use outside core - if you do use it ensure your
 *  code has adequate unit test cover.
 */
trait CRM_Contact_Form_Edit_BlockTrait {

  use CRM_Contact_Form_Edit_BlockCustomDataTrait;

  /**
   * Cached block query results keyed by entity name.
   *
   * @var array
   */
  private array $existingBlocks = [];

  /**
   * Get existing block records for the contact.
   *
   * @param string $entity
   *   The entity name ('Email', 'Phone', 'IM', 'OpenID', 'Website').
   * @param int $startIndex
   *   Start index for array keys (0 for zero-indexed, 1 for 1-indexed).
   *
   * @return array|\Civi\Api4\Generic\Result
   *   Array indexed from $startIndex if $startIndex > 0, otherwise Result object.
   *
   * @throws \CRM_Core_Exception
   */
  public function getExistingBlocks(string $entity, int $startIndex = 0) {
    if (!isset($this->existingBlocks[$entity])) {
      $entityDefinition = \Civi::entity($entity);
      $select = ['*', 'custom.*'];
      if ($entityDefinition->getField('location_type_id')) {
        $select[] = 'location_type_id:label';
      }

      $params = [
        'checkPermissions' => FALSE,
        'select' => $select,
        'where' => [['contact_id', '=', $this->getContactID()]],
      ];

      if ($entityDefinition->getField('is_primary')) {
        $params['orderBy'] = ['is_primary' => 'DESC'];
      }

      $this->existingBlocks[$entity] = civicrm_api4($entity, 'get', $params);
    }

    if ($startIndex > 0) {
      $blocks = (array) $this->existingBlocks[$entity];
      return $blocks ? array_combine(range($startIndex, count($blocks) + $startIndex - 1), $blocks) : [];
    }

    return $this->existingBlocks[$entity];
  }

  /**
   * Save block records for the contact.
   *
   * Updates existing, creates new, and deletes removed records.
   *
   * @param string $entity
   *   The entity name ('Email', 'Phone', 'IM', 'OpenID', 'Website').
   * @param array $records
   *   Incoming block records from the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function saveBlocks(string $entity, array $records): void {
    $dataField = \Civi::entity($entity)->getMeta('label_field');

    $existingRecords = (array) $this->getExistingBlocks($entity)->indexBy('id');
    foreach ($records as $index => $record) {
      $id = $record['id'] ?? NULL;
      $dataExists = !CRM_Utils_System::isNull($record[$dataField] ?? NULL);
      if (!$dataExists) {
        unset($records[$index]);
        continue;
      }
      if (!array_key_exists('contact_id', $record)) {
        $records[$index]['contact_id'] = $this->getContactID();
      }
      if ($id) {
        if (array_key_exists($id, $existingRecords)) {
          unset($existingRecords[$id]);
        }
        else {
          unset($records[$index]['id']);
        }
      }
    }

    if ($records) {
      civicrm_api4($entity, 'save', [
        'checkPermissions' => FALSE,
        'records' => array_values($records),
      ]);
    }

    if (!empty($existingRecords)) {
      civicrm_api4($entity, 'delete', [
        'checkPermissions' => FALSE,
        'where' => [['id', 'IN', array_keys($existingRecords)]],
      ]);
    }
  }

}
