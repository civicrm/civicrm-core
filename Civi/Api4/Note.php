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
 * Note entity.
 *
 * @searchable secondary
 * @since 5.19
 * @parentField entity_id
 * @package Civi\Api4
 */
class Note extends Generic\DAOEntity {
  use Generic\Traits\HierarchicalEntity;

}
