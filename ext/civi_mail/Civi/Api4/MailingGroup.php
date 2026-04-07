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
 * Mailing groups are the groups or mailings included or excluded from mailing recipients.
 *
 * @searchable bridge
 *
 * @see https://docs.civicrm.org/user/en/latest/email/what-is-civimail/
 * @since 5.48
 * @package Civi\Api4
 */
class MailingGroup extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

}
