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
 * GroupNesting entity.
 *
 * @see \Civi\Api4\Group
 * @searchable bridge
 * @since 5.19
 * @package Civi\Api4
 */
class GroupNesting extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;
  use Generic\Traits\ReadOnlyEntity;

  /**
   * @return array
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $info['bridge'] = [
      'child_group_id' => [
        'label' => ts('Children'),
        'description' => ts('Sub-groups nested under this group'),
        'to' => 'parent_group_id',
      ],
    ];
    return $info;
  }

}
