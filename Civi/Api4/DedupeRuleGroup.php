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
 * DedupeRuleGroup entity.
 *
 * This api exposes CiviCRM (dedupe) groups.
 *
 * @searchable none
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/contacts/
 * @since 5.39
 * @package Civi\Api4
 */
class DedupeRuleGroup extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;

}
