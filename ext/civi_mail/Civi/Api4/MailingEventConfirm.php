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
 * Handles double-opt-in confirmations to mailing group subscriptions.
 *
 * @see \Civi\Api4\Mailing
 * @since 5.57
 * @package Civi\Api4
 */
class MailingEventConfirm extends Generic\DAOEntity {
  use \Civi\Api4\Generic\Traits\ReadOnlyEntity;

}
