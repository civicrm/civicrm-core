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
 * Email entity.
 *
 * This entity allows user to add, update, retrieve or delete emails address(es) of a contact.
 *
 * Creating a new email address requires at minimum a contact's ID and email
 *
 * @ui_join_filters is_primary
 *
 * @searchable secondary
 * @since 5.19
 * @package Civi\Api4
 */
class Email extends Generic\DAOEntity {

}
