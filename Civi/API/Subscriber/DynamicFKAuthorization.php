<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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

namespace Civi\API\Subscriber;

use Civi\API\Events;
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
 * either "id" (e.g. for the file) or "entity_table".
 *
 * Note: The permission guard does not exactly authorize the request, but it
 * may veto authorization.
 */
class DynamicFKAuthorization implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return array(
      Events::AUTHORIZE => array(
        array('onApiAuthorize', Events::W_EARLY),
      ),
    );
  }

  /**
   * @var \Civi\API\Kernel
   */
  protected $kernel;

  /**
   * @var string, the entity for which we want to manage permissions
   */
  protected $entityName;

  /**
   * @var array <string> the actions for which we want to manage permissions
   */
  protected $actions;

  /**
   * @var string, SQL. Given a file ID, determine the entity+table it's attached to.
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
   */
  protected $lookupDelegateSql;

  /**
   * @var array list of related tables for which FKs are allowed
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
   * @param array|NULL $allowedDelegates
   *   e.g. "civicrm_mailing","civicrm_activity"; NULL to allow any.
   */
  public function __construct($kernel, $entityName, $actions, $lookupDelegateSql, $allowedDelegates = NULL) {
    $this->kernel = $kernel;
    $this->entityName = $entityName;
    $this->actions = $actions;
    $this->lookupDelegateSql = $lookupDelegateSql;
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
    if ($apiRequest['version'] == 3 && $apiRequest['entity'] == $this->entityName && in_array(strtolower($apiRequest['action']), $this->actions)) {
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
   * @param int|NULL $entityId
   *   The target entity ID.
   * @param array $apiRequest
   *   The full API request.
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function authorizeDelegate($action, $entityTable, $entityId, $apiRequest) {
    $entity = $this->getDelegatedEntityName($entityTable);
    if (!$entity) {
      throw new \API_Exception("Failed to run permission check: Unrecognized target entity ($entityTable)");
    }

    if ($this->isTrusted($apiRequest)) {
      return;
    }

    $params = array('check_permissions' => 1);
    if ($entityId) {
      $params['id'] = $entityId;
    }

    if (!$this->kernel->runAuthorize($entity, $this->getDelegatedAction($action), $params)) {
      throw new \Civi\API\Exception\UnauthorizedException("Authorization failed on ($entity,$entityId)");
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
   */
  public function getDelegate($id) {
    $query = \CRM_Core_DAO::executeQuery($this->lookupDelegateSql, array(
      1 => array($id, 'Positive'),
    ));
    if ($query->fetch()) {
      return array($query->is_valid, $query->entity_table, $query->entity_id);
    }
    else {
      return array(FALSE, NULL, NULL);
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

}
