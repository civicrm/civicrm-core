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
 * Domains - multisite instances of CiviCRM.
 *
 * @see https://docs.civicrm.org/sysadmin/en/latest/setup/multisite/
 *
 * @package Civi\Api4
 */
class Domain extends Generic\DAOEntity {

  public static function get() {
    return new \Civi\Api4\Action\Domain\Get(__CLASS__, __FUNCTION__);
  }

}
