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

use Civi\Api4\Action\ContributionRecur\UpdateAmountOnRecur;

/**
 * ContributionRecur entity.
 *
 * @searchable secondary
 * @since 5.27
 * @package Civi\Api4
 */
class ContributionRecur extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\ContributionRecur\UpdateAmountOnRecur
   */
  public static function updateAmountOnRecur($checkPermissions = TRUE) {
    return (new UpdateAmountOnRecur(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
