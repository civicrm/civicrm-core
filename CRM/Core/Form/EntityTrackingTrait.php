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
 * This trait provides a form of lazy loading for forms.
 *
 * It is intended to reduce the need for forms to pass values around while avoiding
 * constantly reloading them from the database.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
trait CRM_Core_Form_EntityTrackingTrait {

  /**
   * Array of loaded entities.
   *
   * These are stored with keys that are consistent with apiv4 style parameters.
   *
   * @var array
   */
  private $entities = [];

  /**
   * Set the entity to the specified values.
   *
   * @param string $entity
   * @param int $identifier
   * @param array $values
   */
  public function setEntity(string $entity, int $identifier, array $values): void {
    $this->entities[$entity][$identifier] = $values;
  }

  /**
   * Get the value for a property of an entity, loading from the database if needed.
   *
   * Permissions are not applied to the api call.
   *
   * @param string $entity
   * @param int $id
   * @param string $key
   *
   * @api supported for use outside of core. Will not change in a point release.
   *
   * @return mixed
   */
  public function getEntityValue(string $entity, int $id, string $key) {
    if (!isset($this->entities[$entity][$id]) || !array_key_exists($key, $this->entities[$entity][$id])) {
      $this->loadEntity($entity, $id);
    }
    return $this->entities[$entity][$id][$key];
  }

  /**
   * Get a value from a participant record.
   *
   * This function requires that the form implements `getParticipantID()`.
   *
   * @param string $fieldName
   * @param int|null $id If not provided getParticipantID() is called.
   *
   * @api supported for use outside of core. Will not change in a point release.
   *
   * @return mixed
   */
  public function getParticipantValue(string $fieldName, ?int $id = NULL) {
    $id = $id ?? $this->getParticipantID();
    return $this->getEntityValue('Participant', $id, $fieldName);
  }

  /**
   * Get a value from a contact record.
   *
   * This function requires that the form implements `getContactID()`.
   *
   * @param string $fieldName
   * @param int|null $id If not provided getContactID() is called.
   *
   * @api supported for use outside of core. Will not change in a point release.
   *
   * @return mixed
   */
  public function getContactValue(string $fieldName, ?int $id = NULL) {
    $id = $id ?? $this->getContactID();
    return $this->getEntityValue('Contact', $id, $fieldName);
  }

  /**
   * Load the requested entity.
   *
   * If we are going to load an entity we generally load all the values for it.
   *
   * @param string $entity
   * @param int $id
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function loadEntity(string $entity, int $id): void {
    if ($entity === 'Contact') {
      // If we are loading a contact we generally also want their email.
      $select = ['email_primary.email', 'email_primary.on_hold', '*', 'custom.*'];
    }
    else {
      $select = ['*', 'custom.*'];
    }
    $this->entities[$entity][$id] = civicrm_api4($entity, 'get', [
      'where' => [['id', '=', $id]],
      'checkPermissions' => FALSE,
      // @todo - load pseudoconstants too...
      'select' => $select,
    ])->first();
  }

}
