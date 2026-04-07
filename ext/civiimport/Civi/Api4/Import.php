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

use Civi\Api4\Action\GetLinks;
use Civi\Api4\Import\CheckAccessAction;
use Civi\Api4\Generic\DAOGetAction;
use Civi\Api4\Generic\DAOGetFieldsAction;
use Civi\Api4\Action\GetActions;
use Civi\Api4\Import\Create;
use Civi\Api4\Import\Delete;
use Civi\Api4\Import\Save;
use Civi\Api4\Import\Update;
use Civi\Api4\Import\Import as ImportAction;
use Civi\Api4\Import\Validate;

/**
 * Import entity.
 *
 * @searchable secondary
 * @since 5.54
 * @package Civi\Api4
 */
class Import {

  /**
   * Constructor.
   *
   * This is here cos otherwise phpcs complains about the `import` function
   * having the same name as the class.
   */
  public function __construct() {}

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
   * @throws \CRM_Core_Exception
   */
  public static function save(int $userJobID, bool $checkPermissions = TRUE): Save {
    return (new Save('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Import\Create
   *
   * @throws \CRM_Core_Exception
   */
  public static function create(int $userJobID, bool $checkPermissions = TRUE): Create {
    return (new Create('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   * @return \Civi\Api4\Import\Update
   * @throws \CRM_Core_Exception
   */
  public static function update(int $userJobID, bool $checkPermissions = TRUE): Update {
    return (new Update('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   * @return \Civi\Api4\Import\Delete
   * @throws \CRM_Core_Exception
   */
  public static function delete(int $userJobID, bool $checkPermissions = TRUE): Delete {
    return (new Delete('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $userJobID
   * @param bool $checkPermissions
   * @return Generic\BasicReplaceAction
   * @throws \CRM_Core_Exception
   */
  public static function replace($userJobID, $checkPermissions = TRUE) {
    return (new Generic\BasicReplaceAction("Import_$userJobID", __FUNCTION__))
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
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Action\GetLinks
   */
  public static function getLinks(int $userJobID, bool $checkPermissions = TRUE): GetLinks {
    return (new GetLinks('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @return \Civi\Api4\Generic\CheckAccessAction
   * @throws \CRM_Core_Exception
   */
  public static function checkAccess(int $userJobID): CheckAccessAction {
    return new CheckAccessAction('Import_' . $userJobID, __FUNCTION__);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Import\Import
   *
   */
  public static function import(int $userJobID, bool $checkPermissions = TRUE): ImportAction {
    return (new ImportAction('Import_' . $userJobID, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param int $userJobID
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Import\Validate
   */
  public static function validate(int $userJobID, bool $checkPermissions = TRUE): Validate {
    return (new Validate('Import_' . $userJobID, __FUNCTION__))->setCheckPermissions($checkPermissions);
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
