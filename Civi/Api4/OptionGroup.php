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
 * OptionGroup entity.
 *
 * @see \Civi\Api4\OptionValue
 * @searchable secondary
 * @since 5.19
 * @package Civi\Api4
 */
class OptionGroup extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;

}
