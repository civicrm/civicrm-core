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
 * UFGroup entity - AKA profiles.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/profiles/
 * @searchable none
 * @since 5.19
 * @package Civi\Api4
 */
class UFGroup extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;

}
