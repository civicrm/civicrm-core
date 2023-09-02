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
namespace Civi\API;

trait EntityLookupTrait {

  /**
   * Array of defined entity identifiers.
   *
   * @var array
   */
  private $entityLookupDefinitions = [];

  /**
   * Array of defined entity values.
   *
   * @var array
   */
  private $entityLookupValues = [];

  /**
   * Defines a record so its values can be retrieved using `$this->lookup()`
   *
   * @param string $apiEntityName
   * @param string $nickname
   *   Handle to use to retrieve values with `$this->lookup()`
   * @param array $identifier
   *   A unique key or combination of keys to uniquely identify the record (usually id)
   *   Most commonly looks like `['id' => 123]`
   */
  protected function define(string $apiEntityName, string $nickname, array $identifier): void {
    $this->entityLookupDefinitions[$nickname] = [
      'entityName' => $apiEntityName,
      'identifier' => $identifier,
    ];
    $this->entityLookupValues[$nickname] = [];
  }

  /**
   * Retrieve a field value for a defined entity
   *
   * @param string $nickname
   *   Handle set by `$this->define()`
   * @param string $fieldName
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function lookup(string $nickname, string $fieldName) {
    if (!isset($this->entityLookupValues[$nickname])) {
      throw new \CRM_Core_Exception(sprintf('Cannot lookup entity "%s" before it has been defined.', $nickname));
    }
    if (array_key_exists($fieldName, $this->entityLookupValues[$nickname])) {
      return $this->entityLookupValues[$nickname][$fieldName];
    }
    $entityName = $this->entityLookupDefinitions[$nickname]['entityName'];
    $params = [
      'select' => [$fieldName],
      'where' => [],
      'checkPermissions' => FALSE,
    ];
    foreach ($this->entityLookupDefinitions[$nickname]['identifier'] as $key => $val) {
      $params['where'][] = [$key, '=', $val];
    }
    if (!$this->entityLookupValues[$nickname]) {
      $params['select'][] = '*';
      if ($entityName === 'Contact') {
        $params['select'][] = 'email_primary.*';
      }
    }
    // If requesting a join or a custom field, fetch them all by replacing the last part with a *
    if (str_contains($fieldName, '.')) {
      $parts = explode('.', $fieldName);
      $parts[count($parts) - 1] = '*';
      $params['select'][] = implode('.', $parts);
    }
    $retrieved = civicrm_api4($entityName, 'get', $params)->single();
    $this->entityLookupValues[$nickname] += $retrieved;
    return $this->entityLookupValues[$nickname][$fieldName] ?? NULL;
  }

}
