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
namespace Civi\Api4;

use Civi\Api4\Generic\BasicReplaceAction;
use Civi\Api4\Generic\CheckAccessAction;
use Civi\Api4\Generic\DAOCreateAction;
use Civi\Api4\Generic\DAODeleteAction;
use Civi\Api4\Generic\DAOGetAction;
use Civi\Api4\Generic\DAOGetFieldsAction;
use Civi\Api4\Action\GetActions;
use Civi\Api4\Import\Save;
use Civi\Api4\Import\Update;

/**
 * Import entity.
 *
 * @searchable secondary
 * @since 5.54
 * @package Civi\Api4
 */
class Import {

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Generic\DAOGetFieldsAction
   */
  public static function getFields(int $userJobID, bool $checkPermissions = TRUE): DAOGetFieldsAction {
    return (new DAOGetFieldsAction('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\DAOGetAction
   * @throws \CRM_Core_Exception
   */
  public static function get(int $userJobID, bool $checkPermissions = TRUE): DAOGetAction {
    return (new DAOGetAction('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   * @return \Civi\Api4\Import\Save
   * @throws \API_Exception
   */
  public static function save(int $userJobID, bool $checkPermissions = TRUE): Save {
    return (new Save('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\DAOCreateAction
   * @throws \API_Exception
   */
  public static function create(int $userJobID, bool $checkPermissions = TRUE): DAOCreateAction {
    return (new DAOCreateAction('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   * @return \Civi\Api4\Import\Update
   * @throws \API_Exception
   */
  public static function update(int $userJobID, bool $checkPermissions = TRUE): Update {
    return (new Update('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\DAODeleteAction
   * @throws \API_Exception
   */
  public static function delete(int $userJobID, bool $checkPermissions = TRUE): DAODeleteAction {
    return (new DAODeleteAction('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\BasicReplaceAction
   * @throws \API_Exception
   */
  public static function replace(int $userJobID, bool $checkPermissions = TRUE): BasicReplaceAction {
    return (new BasicReplaceAction('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\GetActions
   */
  public static function getActions(int $userJobID, bool $checkPermissions = TRUE): GetActions {
    return (new GetActions('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @return \Civi\Api4\Generic\CheckAccessAction
   * @throws \API_Exception
   */
  public static function checkAccess(int $userJobID): CheckAccessAction {
    return new CheckAccessAction('Import_' . $userJobID, __FUNCTION__);
  }

  /**
   * We need to implement these elsewhere as we permit based on 'created_id'.
   *
   * @return array
   */
  public static function permissions(): array {
    return [];
  }

}
