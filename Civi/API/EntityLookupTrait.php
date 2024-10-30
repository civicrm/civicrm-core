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
   * Check if an entity can be looked up
   *
   * @param string $nickname
   * @return bool
   */
  public function isDefined(string $nickname): bool {
    return !is_null($this->getDefinition($nickname));
  }

  /**
   * Retrieve entity definition (entityName string, identifier [keys/values])
   *
   * @param string $nickname
   * @return array{entityName: string, identifier: array}|null
   */
  protected function getDefinition(string $nickname): ?array {
    return $this->entityLookupDefinitions[$nickname] ?? NULL;
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
    $definition = $this->getDefinition($nickname);
    if (!$definition) {
      throw new \CRM_Core_Exception(sprintf('Cannot lookup entity "%s" before it has been defined.', $nickname));
    }
    // Simply return an id - no need for any queries
    if (isset($definition['identifier'][$fieldName])) {
      return $definition['identifier'][$fieldName];
    }
    // Return stored value from previous lookup
    if (array_key_exists($fieldName, $this->entityLookupValues[$nickname])) {
      return $this->entityLookupValues[$nickname][$fieldName];
    }
    $params = [
      'select' => [$fieldName],
      'where' => [],
      'checkPermissions' => FALSE,
    ];
    foreach ($definition['identifier'] as $key => $val) {
      $params['where'][] = [$key, '=', $val];
    }
    // Initial load - prefetch all core fields to reduce # of subsequent queries
    if (!$this->entityLookupValues[$nickname]) {
      $params['select'][] = '*';
      // Contact email is commonly needed by forms so prefetch it as well
      if ($definition['entityName'] === 'Contact') {
        $params['select'][] = 'email_primary.*';
      }
    }
    // If requesting a join or a custom field, prefetch all using `select 'join_entity.*'`
    if (str_contains($fieldName, '.')) {
      $parts = explode('.', $fieldName);
      $parts[count($parts) - 1] = '*';
      $params['select'][] = implode('.', $parts);
    }
    $retrieved = civicrm_api4($definition['entityName'], 'get', $params)->single();
    $this->entityLookupValues[$nickname] += $retrieved;
    return $this->entityLookupValues[$nickname][$fieldName] ?? NULL;
  }

}
