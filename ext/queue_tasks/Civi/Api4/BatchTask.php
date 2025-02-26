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

use Civi\Api4\Generic\DAOGetFieldsAction;
use Civi\Api4\Action\BatchTask\Insert;

/**
 * Import entity.
 *
 * @package Civi\Api4
 */
class BatchTask {

  /**
   * Constructor.
   *
   * This is here cos otherwise phpcs complains about the `import` function
   * having the same name as the class.
   */
  public function __construct() {}

  /**
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Generic\DAOGetFieldsAction
   */
  public static function getFields(bool $checkPermissions = TRUE): DAOGetFieldsAction {
    return (new DAOGetFieldsAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Action\BatchTask\Insert
   */
  public static function insert(bool $checkPermissions = TRUE): Insert {
    return (new Insert(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
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
