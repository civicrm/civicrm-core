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

namespace Civi\Import;

use Civi\api4\Contact;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class to parse contribution csv files.
 */
abstract class ImportParser extends \CRM_Import_Parser {

  /**
   * Validate that the mapping has the required fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function validateMapping($mapping): void {
    $mappedFields = [];
    foreach ($mapping as $mappingField) {
      // Civiimport uses MappingField['name'] - $mappingField[0] is (soft) deprecated.
      $mappingFieldName = $mappingField['name'] ?? $mappingField[0] ?? '';
      $mappedFields[$mappingFieldName] = $mappingFieldName;
    }
    $entity = $this->baseEntity;
    $missingFields = $this->getMissingFields($this->getRequiredFieldsForEntity($entity, $this->getActionForEntity($entity)), $mappedFields);
    if (!empty($missingFields)) {
      $error = [];
      foreach ($missingFields as $missingField) {
        $error[] = ts('Missing required field: %1', [1 => $missingField]);
      }
      throw new \CRM_Core_Exception(implode('<br/>', $error));
    }
  }

  /**
   * Get the actions to display in the rich UI.
   *
   * Filter by the input actions - e.g ['update' 'select'] will only return those keys.
   *
   * @param array $actions
   * @param string $entity
   *
   * @return array
   */
  protected function getActions(array $actions, $entity = 'Contact'): array {
    $actionList['Contact'] = [
      'ignore' => [
        'id' => 'ignore',
        'text' => ts('No action'),
        'description' => ts('Contact not altered'),
      ],
      'select' => [
        'id' => 'select',
        'text' => ts('Match existing Contact'),
        'description' => ts('Look up existing contact. Skip row if not found'),
      ],
      'update' => [
        'id' => 'update',
        'text' => ts('Update existing Contact.'),
        'description' => ts('Update existing Contact. Skip row if not found'),
      ],
      'save' => [
        'id' => 'save',
        'text' => ts('Update existing Contact or Create'),
        'description' => ts('Create new contact if not found'),
      ],
    ];
    return array_values(array_intersect_key($actionList[$entity], array_fill_keys($actions, TRUE)));
  }

  /**
   * Save the contact.
   *
   * @param string $entity
   * @param array $contact
   *
   * @return int|null
   *
   * @throws \Civi\API\Exception\UnauthorizedException|\CRM_Core_Exception
   */
  protected function saveContact(string $entity, array $contact): ?int {
    if (in_array($this->getActionForEntity($entity), ['update', 'save', 'create'])) {
      return Contact::save()
        ->setRecords([$contact])
        ->execute()
        ->first()['id'];
    }
    return NULL;
  }

}
