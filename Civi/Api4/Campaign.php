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
 * Campaign entity.
 *
 * @package Civi\Api4
 */
class Campaign extends Generic\DAOEntity {

  /**
   * @return \Civi\Api4\Action\Campaign\Get
   */
  public static function get() {
    return new \Civi\Api4\Action\Campaign\Get(__CLASS__, __FUNCTION__);
  }

}
