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
 * CaseActivity BridgeEntity.
 *
 * This connects an activity to one or more cases.
 *
 * @searchable bridge
 * @see \Civi\Api4\Case
 * @since 5.37
 * @package Civi\Api4
 */
class CaseActivity extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

}
