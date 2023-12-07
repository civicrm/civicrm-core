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
 * Configurable event participant statuses.
 *
 * @see \Civi\Api4\Participant
 *
 * @orderBy weight
 * @since 5.61
 * @package Civi\Api4
 */
class ParticipantStatusType extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;
  use Generic\Traits\SortableEntity;

}
