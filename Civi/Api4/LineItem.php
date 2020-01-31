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
 * LineItem Entity.
 *
 * @see https://docs.civicrm.org/dev/en/latest/financial/overview/
 *
 * @package Civi\Api4
 */
class LineItem extends Generic\DAOEntity {

  /**
   * @return \Civi\Api4\Action\LineItem\Create
   */
  public static function create() {
    return new \Civi\Api4\Action\LineItem\Create(__CLASS__, __FUNCTION__);
  }

  /**
   * @return \Civi\Api4\Action\LineItem\Save
   */
  public static function save() {
    return new \Civi\Api4\Action\LineItem\Save(__CLASS__, __FUNCTION__);
  }

  /**
   * @return \Civi\Api4\Action\LineItem\Update
   */
  public static function update() {
    return new \Civi\Api4\Action\LineItem\Update(__CLASS__, __FUNCTION__);
  }

}
