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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */

namespace Civi\Api4;

/**
 * GroupContact entity - link between groups and contacts.
 *
 * A contact can either be "Added" "Removed" or "Pending" in a group.
 * CiviCRM only considers them to be "in" a group if their status is "Added".
 *
 * @see \Civi\Api4\Group
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
