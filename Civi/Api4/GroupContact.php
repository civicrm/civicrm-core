<?php

namespace Civi\Api4;

/**
 * GroupContact entity - link between groups and contacts.
 *
 * A contact can either be "Added" "Removed" or "Pending" in a group.
 * CiviCRM only considers them to be "in" a group if their status is "Added".
 *
 * @package Civi\Api4
 */
class GroupContact extends Generic\DAOEntity {

  /**
   * @return Action\GroupContact\Create
   */
  public static function create() {
    return new Action\GroupContact\Create(__CLASS__, __FUNCTION__);
  }

  /**
   * @return Action\GroupContact\Save
   */
  public static function save() {
    return new Action\GroupContact\Save(__CLASS__, __FUNCTION__);
  }

  /**
   * @return Action\GroupContact\Update
   */
  public static function update() {
    return new Action\GroupContact\Update(__CLASS__, __FUNCTION__);
  }

}
