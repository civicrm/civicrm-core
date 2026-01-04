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
 * Address Entity.
 *
 * This entity holds the address information of a contact. Each contact may hold
 * one or more addresses but must have different location types respectively.
 *
 * Creating a new address requires at minimum a contact's ID and location type ID
 * and other attributes (although optional) like street address, city, country etc.
 *
 * @ui_join_filters is_primary
 * @searchFields street_address,city
 *
 * @searchable secondary
 * @since 5.19
 * @package Civi\Api4
 */
class Address extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Address\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\Address\Create(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Address\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\Address\Save(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Address\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\Address\Update(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Address\GetCoordinates
   */
  public static function getCoordinates($checkPermissions = TRUE) {
    return (new Action\Address\GetCoordinates(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
