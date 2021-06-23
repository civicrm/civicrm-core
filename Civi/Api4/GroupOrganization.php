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
 * GroupOrganization entity.
 *
 * Relates groups to organizations.
 *
 * FIXME: For now, excluding this from SearchKit because it's confusingly similar to GroupContact
 * @searchable none
 *
 * @see \Civi\Api4\Group
 * @since 5.19
 * @package Civi\Api4
 */
class GroupOrganization extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

}
