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

namespace Civi\API\Subscriber;

use Civi\API\Events;
use CRM_Core_DAO_AllCoreTables as AllCoreTables;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Given an entity which dynamically attaches itself to another entity,
 * determine if one has permission to the other entity.
 *
 * Example: Suppose one tries to manipulate a File which is attached to a
 * Mailing. DynamicFKAuthorization will enforce permissions on the File by
 * imitating the permissions of the Mailing.
 *
 * Note: This enforces a constraint: all matching API calls must define
 * "id" (e.g. for the file) or "entity_table+entity_id" or
 * "field_name+entity_id".
 *
 * Note: The permission guard does not exactly authorize the request, but it
 * may veto authorization.
 */
class DynamicFKAuthorization implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.authorize' => [
        ['onApiAuthorize', Events::W_EARLY],
      ],
    ];
  }

  /**
   * @var \Civi\API\Kernel
   *
   * Treat as private. Marked public due to PHP 5.3-compatibility issues.
   */
  public $kernel;

  /**
   * The entity for which we want to manage permissions.
   *
   * @var string
   */
  protected $entityName;

  /**
   * The actions for which we want to manage permissions
   *
   * @var string[]
   */
  protected $actions;

  /**
   * SQL SELECT query - Given a file ID, determine the entity+table it's attached to.
   *
   * ex: "SELECT if(cf.id,1,0) as is_valid, cef.entity_table, cef.entity_id
   * FROM civicrm_file cf
   * INNER JOIN civicrm_entity_file cef ON cf.id = cef.file_id
   * WHERE cf.id = %1"
   *
   * Note: %1 is a parameter
   * Note: There are three parameters
   *  - is_valid: "1" if %1 identifies an actual record; otherwise "0"
   *  - entity_table: NULL or the name of a related table
   *  - entity_id: NULL or the ID of a row in the related table
   *
   * @var string
   */
  protected $lookupDelegateSql;

  /**
   * SQL SELECT query. Get a list of (field_name, table_name, extends) tuples.
   *
   * For example, one tuple might be ("custom_123", "civicrm_value_mygroup_4",
   * "Activity").
   *
   * @var string
   */
  protected $lookupCustomFieldSql;

  /**
   * @var array
   *
   * Each item is an array(field_name => $, table_name => $, extends => $)
   */
  protected $lookupCustomFieldCache;

  /**
   * List of related tables for which FKs are allowed.
   *
   * @var array
   */
  protected $allowedDelegates;

  /**
   * @param \Civi\API\Kernel $kernel
   *   The API kernel.
   * @param string $entityName
   *   The entity for which we want to manage permissions (e.g. "File" or
   *   "Note").
   * @param array $actions
   *   The actions for which we want to manage permissions (e.g. "create",
   *   "get", "delete").
   * @param string $lookupDelegateSql
   *   See docblock in DynamicFKAuthorization::$lookupDelegateSql.
   * @param string $lookupCustomFieldSql
   *   See docblock in DynamicFKAuthorization::$lookupCustomFieldSql.
   * @param array|NULL $allowedDelegates
   *   e.g. "civicrm_mailing","civicrm_activity"; NULL to allow any.
   */
  public function __construct($kernel, $entityName, $actions, $lookupDelegateSql, $lookupCustomFieldSql, $allowedDelegates = NULL) {
    $this->kernel = $kernel;
    $this->entityName = AllCoreTables::convertEntityNameToCamel($entityName, TRUE);
    $this->actions = $actions;
    $this->lookupDelegateSql = $lookupDelegateSql;
    $this->lookupCustomFieldSql = $lookupCustomFieldSql;
    $this->allowedDelegates = $allowedDelegates;
  }

  /**
   * @param \Civi\API\Event\AuthorizeEvent $event
   *   API authorization event.
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function onApiAuthorize(\Civi\API\Event\AuthorizeEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['version'] == 3 && AllCoreTables::convertEntityNameToCamel($apiRequest['entity'], TRUE) == $this->entityName && in_array(strtolower($apiRequest['action']), $this->actions)) {
      if (isset($apiRequest['params']['field_name'])) {
        $fldIdx = \CRM_Utils_Array::index(['field_name'], $this->getCustomFields());
        if (empty($fldIdx[$apiRequest['params']['field_name']])) {
          throw new \Exception("Failed to map custom field to entity table");
        }
        $apiRequest['params']['entity_table'] = $fldIdx[$apiRequest['params']['field_name']]['entity_table'];
        unset($apiRequest['params']['field_name']);
      }

      if (/*!$isTrusted */
        empty($apiRequest['params']['id']) && empty($apiRequest['params']['entity_table'])
      ) {
        throw new \API_Exception("Mandatory key(s) missing from params array: 'id' or 'entity_table'");
      }

      if (isset($apiRequest['params']['id'])) {
        list($isValidId, $entityTable, $entityId) = $this->getDelegate($apiRequest['params']['id']);
        if ($isValidId && $entityTable && $entityId) {
          $this->authorizeDelegate($apiRequest['action'], $entityTable, $entityId, $apiRequest);
          $this->preventReassignment($apiRequest['params']['id'], $entityTable, $entityId, $apiRequest);
          return;
        }
        elseif ($isValidId) {
          throw new \API_Exception("Failed to match record to related entity");
        }
        elseif (!$isValidId && strtolower($apiRequest['action']) == 'get') {
          // The matches will be an empty set; doesn't make a difference if we
          // reject or accept.
          // To pass SyntaxConformanceTest, we won't veto "get" on empty-set.
          return;
        }
      }

      if (isset($apiRequest['params']['entity_table'])) {
        if (!\CRM_Core_DAO_AllCoreTables::isCoreTable($apiRequest['params']['entity_table'])) {
          throw new \API_Exception("Unrecognized target entity table {$apiRequest['params']['entity_table']}");
        }
        $this->authorizeDelegate(
          $apiRequest['action'],
          $apiRequest['params']['entity_table'],
          \CRM_Utils_Array::value('entity_id', $apiRequest['params'], NULL),
          $apiRequest
        );
        return;
      }

      throw new \API_Exception("Failed to run permission check");
    }
  }

  /**
   * @param string $action
   *   The API action (e.g. "create").
   * @param string $entityTable
   *   The target entity table (e.g. "civicrm_mailing").
   * @param int|null $entityId
   *   The target entity ID.
   * @param array $apiRequest
   *   The full API request.
   * @throws \Exception
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function authorizeDelegate($action, $entityTable, $entityId, $apiRequest) {
    if ($this->isTrusted($apiRequest)) {
      return;
    }

    $entity = $this->getDelegatedEntityName($entityTable);
    if (!$entity) {
      throw new \API_Exception("Failed to run permission check: Unrecognized target entity table ($entityTable)");
    }
    if (!$entityId) {
      throw new \Civi\API\Exception\UnauthorizedException("Authorization failed on ($entity): Missing entity_id");
    }

    /**
     * @var \Exception $exception
     */
    $exception = NULL;
    $self = $this;
    \CRM_Core_Transaction::create(TRUE)->run(function($tx) use ($entity, $action, $entityId, &$exception, $self) {
      // Just to be safe.
      $tx->rollback();

      $params = [
        'version' => 3,
        'check_permissions' => 1,
        'id' => $entityId,
      ];

      $result = $self->kernel->runSafe($entity, $self->getDelegatedAction($action), $params);
      if ($result['is_error'] || empty($result['values'])) {
        $exception = new \Civi\API\Exception\UnauthorizedException("Authorization failed on ($entity,$entityId)", [
          'cause' => $result,
        ]);
      }
    });

    if ($exception) {
      throw $exception;
    }
  }

  /**
   * If the request attempts to change the entity_table/entity_id of an
   * existing record, then generate an error.
   *
   * @param int $fileId
   *   The main record being changed.
   * @param string $entityTable
   *   The saved FK.
   * @param int $entityId
   *   The saved FK.
   * @param array $apiRequest
   *   The full API request.
   * @throws \API_Exception
   */
  public function preventReassignment($fileId, $entityTable, $entityId, $apiRequest) {
    if (strtolower($apiRequest['action']) == 'create' && $fileId && !$this->isTrusted($apiRequest)) {
      // TODO: no change in field_name?
      if (isset($apiRequest['params']['entity_table']) && $entityTable != $apiRequest['params']['entity_table']) {
        throw new \API_Exception("Cannot modify entity_table");
      }
      if (isset($apiRequest['params']['entity_id']) && $entityId != $apiRequest['params']['entity_id']) {
        throw new \API_Exception("Cannot modify entity_id");
      }
    }
  }

  /**
   * @param string $entityTable
   *   The target entity table (e.g. "civicrm_mailing" or "civicrm_activity").
   * @return string|NULL
   *   The target entity name (e.g. "Mailing" or "Activity").
   */
  public function getDelegatedEntityName($entityTable) {
    if ($this->allowedDelegates === NULL || in_array($entityTable, $this->allowedDelegates)) {
      $className = \CRM_Core_DAO_AllCoreTables::getClassForTable($entityTable);
      if ($className) {
        $entityName = \CRM_Core_DAO_AllCoreTables::getBriefName($className);
        if ($entityName) {
          return $entityName;
        }
      }
    }
    return NULL;
  }

  /**
   * @param string $action
   *   API action name -- e.g. "create" ("When running *create* on a file...").
   * @return string
   *   e.g. "create" ("Check for *create* permission on the mailing to which
   *   it is attached.")
   */
  public function getDelegatedAction($action) {
    switch ($action) {
      case 'get':
        // reading attachments requires reading the other entity
        return 'get';

      case 'create':
      case 'delete':
        // creating/updating/deleting an attachment requires editing
        // the other entity
        return 'create';

      default:
        return $action;
    }
  }

  /**
   * @param int $id
   *   e.g. file ID.
   * @return array
   *   (0 => bool $isValid, 1 => string $entityTable, 2 => int $entityId)
   * @throws \Exception
   */
  public function getDelegate($id) {
    $query = \CRM_Core_DAO::executeQuery($this->lookupDelegateSql, [
      1 => [$id, 'Positive'],
    ]);
    if ($query->fetch()) {
      if (!preg_match('/^civicrm_value_/', $query->entity_table)) {
        // A normal attachment directly on its entity.
        return [$query->is_valid, $query->entity_table, $query->entity_id];
      }

      // Ex: Translate custom-field table ("civicrm_value_foo_4") to
      // entity table ("civicrm_activity").
      $tblIdx = \CRM_Utils_Array::index(['table_name'], $this->getCustomFields());
      if (isset($tblIdx[$query->entity_table])) {
        return [$query->is_valid, $tblIdx[$query->entity_table]['entity_table'], $query->entity_id];
      }
      throw new \Exception('Failed to lookup entity table for custom field.');
    }
    else {
      return [FALSE, NULL, NULL];
    }
  }

  /**
   * @param array $apiRequest
   *   The full API request.
   * @return bool
   */
  public function isTrusted($apiRequest) {
    // isn't this redundant?
    return empty($apiRequest['params']['check_permissions']) or $apiRequest['params']['check_permissions'] == FALSE;
  }

  /**
   * @return array
   *   Each item has keys 'field_name', 'table_name', 'extends', 'entity_table'
   */
  public function getCustomFields() {
    $query = \CRM_Core_DAO::executeQuery($this->lookupCustomFieldSql);
    $rows = [];
    while ($query->fetch()) {
      $rows[] = [
        'field_name' => $query->field_name,
        'table_name' => $query->table_name,
        'extends' => $query->extends,
        'entity_table' => \CRM_Core_BAO_CustomGroup::getTableNameByEntityName($query->extends),
      ];
    }
    return $rows;
  }

}
