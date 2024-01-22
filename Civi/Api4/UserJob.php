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
 * UserJob entity.
 *
 * This entity allows tracking of imports, including associated temp tables.
 *
 * @searchable secondary
 * @since 5.50
 * @package Civi\Api4
 */
class UserJob extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;

}
