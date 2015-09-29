<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
    return array(
      Events::RESOLVE => array(
        array('onApiResolve', Events::W_MIDDLE),
      ),
      Events::AUTHORIZE => array(
        array('onApiAuthorize', Events::W_MIDDLE),
      ),
    );
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
  public function __construct($version, $entity, $fields, $perms = array(), $records = array()) {
    parent::__construct($version, $entity);

    $perms = array_merge(array(
      'create' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'get' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'delete' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ), $perms);

    $this->records = \CRM_Utils_Array::index(array('id'), $records);
    $this->fields = $fields;

    $this->addAction('create', $perms['create'], array($this, 'doCreate'));
    $this->addAction('get', $perms['get'], array($this, 'doGet'));
    $this->addAction('delete', $perms['delete'], array($this, 'doDelete'));
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
   *    Formatted API result
   * @throws \API_Exception
   */
  public function doCreate($apiRequest) {
    if (isset($apiRequest['params']['id'])) {
      $id = $apiRequest['params']['id'];
    }
    else {
      $id = max(array_keys($this->records)) + 1;
      $this->records[$id] = array();
    }

    if (!isset($this->records[$id])) {
      throw new \API_Exception("Invalid ID: $id");
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
   *    Formatted API result
   * @throws \API_Exception
   */
  public function doGet($apiRequest) {
    return _civicrm_api3_basic_array_get($apiRequest['entity'], $apiRequest['params'], $this->records, 'id', $this->fields);
  }

  /**
   * @param array $apiRequest
   *   The full description of the API request.
   * @return array
   *    Formatted API result
   * @throws \API_Exception
   */
  public function doDelete($apiRequest) {
    $id = @$apiRequest['params']['id'];
    if ($id && isset($this->records[$id])) {
      unset($this->records[$id]);
    }
    return civicrm_api3_create_success(array());
  }

}
