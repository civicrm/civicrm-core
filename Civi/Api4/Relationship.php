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
 * Relationship entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/relationships/
 *
 * @package Civi\Api4
 */
class Relationship extends Generic\DAOEntity {

  /**
   * @return \Civi\Api4\Action\Relationship\Get
   */
  public static function get() {
    return new \Civi\Api4\Action\Relationship\Get(static::class, __FUNCTION__);
  }

}
