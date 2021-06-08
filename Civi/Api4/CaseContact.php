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
 */


namespace Civi\Api4;

/**
 * CaseContact BridgeEntity.
 *
 * This connects a client to a case.
 *
 * @searchable bridge
 * @see \Civi\Api4\Case
 * @package Civi\Api4
 */
class CaseContact extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

  protected static function getEntityTitle($plural = FALSE) {
    return $plural ? ts('Case Clients') : ts('Case Client');
  }

}
