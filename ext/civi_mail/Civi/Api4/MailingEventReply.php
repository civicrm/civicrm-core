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
 * Tracks when a contact replies to a mailing.
 *
 * @see \Civi\Api4\Mailing
 * @since 5.64
 * @package Civi\Api4
 */
class MailingEventReply extends Generic\DAOEntity {
  use Generic\Traits\ReadOnlyEntity;

}
