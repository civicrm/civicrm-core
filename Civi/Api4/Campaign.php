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

/**
 * Campaign entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/campaign/what-is-civicampaign/
 * @searchable secondary
 * @since 5.19
 * @package Civi\Api4
 */
class Campaign extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Campaign\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Campaign\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
