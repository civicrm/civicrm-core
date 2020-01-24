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
 * CiviCRM settings api.
 *
 * Used to read/write persistent setting data from CiviCRM.
 *
 * @see \Civi\Core\SettingsBag
 * @package Civi\Api4
 */
class Setting extends Generic\AbstractEntity {

  public static function get() {
    return new Action\Setting\Get(__CLASS__, __FUNCTION__);
  }

  public static function set() {
    return new Action\Setting\Set(__CLASS__, __FUNCTION__);
  }

  public static function revert() {
    return new Action\Setting\Revert(__CLASS__, __FUNCTION__);
  }

  public static function getFields() {
    return new Action\Setting\GetFields(__CLASS__, __FUNCTION__);
  }

}
