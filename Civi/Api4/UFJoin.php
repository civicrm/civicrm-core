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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4;

/**
 * UFJoin entity - links profiles to the components/extensions they are used for.
 *
 * @see \Civi\Api4\UFGroup
 * @searchable false
 * @package Civi\Api4
 */
class UFJoin extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

}
