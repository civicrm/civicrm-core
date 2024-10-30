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
 * CaseType Entity.
 *
 * This contains configuration settings for each type of CiviCase.
 *
 * @see \Civi\Api4\Case
 * @searchable secondary
 * @since 5.37
 * @package Civi\Api4
 */
class CaseType extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;

}
