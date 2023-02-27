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

namespace Civi\API\Provider;

use Civi\API\Events;

/**
 * A static provider is useful for creating mock API implementations which
 * manages records in-memory.
 *
 * TODO Add a static provider to SyntaxConformanceTest to ensure that it's
 * representative.
 */
class StaticProvider extends AdhocProvider {
  protected $records;
  protected $fields;

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.resolve' => [
        ['onApiResolve', Events::W_MIDDLE],
      ],
      'civi.api.authorize' => [
        ['onApiAuthorize', Events::W_MIDDLE],
      ],
    ];
  }

  /**
   * @param int $version
   *   API version.
   * @param string $entity
   *   API entity.
   * @param array $fields
   *   List of fields in this fake entity.
   * @param array $perms
   *   Array(string $action => string $perm).
   * @param array $records
   *   List of mock records to be read/updated by API calls.
   */
  public function __construct($version, $entity, $fields, $perms = [], $records = []) {
    parent::__construct($version, $entity);

    $perms = array_merge([
      'create' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'get' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'delete' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ], $perms);

    $this->records = \CRM_Utils_Array::index(['id'], $records);
    $this->fields = $fields;

    $this->addAction('create', $perms['create'], [$this, 'doCreate']);
    $this->addAction('get', $perms['get'], [$this, 'doGet']);
    $this->addAction('delete', $perms['delete'], [$this, 'doDelete']);
  }

  /**
   * @return array
   */
  public function getRecords() {
    return $this->records;
  }

  /**
   * @param array $records
   *   List of mock records to be read/updated by API calls.
   */
  public function setRecords($records) {
    $this->records = $records;
  }

  /**
   * @param array $apiRequest
   *   The full description of the API request.
   * @return array
   *   Formatted API result
   * @throws \CRM_Core_Exception
   */
  public function doCreate($apiRequest) {
    if (isset($apiRequest['params']['id'])) {
      $id = $apiRequest['params']['id'];
    }
    else {
      $id = max(array_keys($this->records)) + 1;
      $this->records[$id] = [];
    }

    if (!isset($this->records[$id])) {
      throw new \CRM_Core_Exception("Invalid ID: $id");
    }

    foreach ($this->fields as $field) {
      if (isset($apiRequest['params'][$field])) {
        $this->records[$id][$field] = $apiRequest['params'][$field];
      }
    }

    return civicrm_api3_create_success($this->records[$id]);
  }

  /**
   * @param array $apiRequest
   *   The full description of the API request.
   * @return array
   *   Formatted API result
   * @throws \CRM_Core_Exception
   */
  public function doGet($apiRequest) {
    return _civicrm_api3_basic_array_get($apiRequest['entity'], $apiRequest['params'], $this->records, 'id', $this->fields);
  }

  /**
   * @param array $apiRequest
   *   The full description of the API request.
   * @return array
   *   Formatted API result
   * @throws \CRM_Core_Exception
   */
  public function doDelete($apiRequest) {
    $id = @$apiRequest['params']['id'];
    if ($id && isset($this->records[$id])) {
      unset($this->records[$id]);
    }
    return civicrm_api3_create_success([]);
  }

}
