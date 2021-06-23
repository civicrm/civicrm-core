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
 * UFMatch entity - links civicrm contacts with users created externally
 *
 * @searchable none
 * @since 5.19
 * @package Civi\Api4
 */
class UFMatch extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

}
