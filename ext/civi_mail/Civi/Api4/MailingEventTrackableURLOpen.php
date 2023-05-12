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
 * Tracks clickthrough events when users open links in mailings.
 *
 * @see \Civi\Api4\MailingTrackableURL
 * @since 5.62
 * @package Civi\Api4
 */
class MailingEventTrackableURLOpen extends Generic\DAOEntity {
  use \Civi\Api4\Generic\Traits\ReadOnlyEntity;

}
