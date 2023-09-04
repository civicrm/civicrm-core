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
 * EntityTag - links tags to contacts, activities, etc.
 *
 * @see \Civi\Api4\Tag
 * @searchable bridge
 * @since 5.19
 * @package Civi\Api4
 */
class EntityTag extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

}
