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
 * Event entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/events/what-is-civievent/
 *
 * @package Civi\Api4
 */
class Event extends Generic\DAOEntity {

  /**
   * @return \Civi\Api4\Action\Event\Get
   */
  public static function get() {
    return new \Civi\Api4\Action\Event\Get(__CLASS__, __FUNCTION__);
  }

}
