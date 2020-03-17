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
 * $Id$
 *
 */


namespace Civi\Api4;

/**
 * ACL (Access Control List).
 *
 * An ACL record consists of:
 *
 *   1. An Operation (e.g. 'View' or 'Edit').
 *   2. A set of Data that the operation can be performed on (e.g. a group of contacts).
 *   3. A Role that has permission to do this operation.
 *
 * Creating a new ACL requires at minimum an entity table, entity ID and object_table.
 *
 * @see https://docs.civicrm.org/user/en/latest/initial-set-up/permissions-and-access-control
 * @package Civi\Api4
 */
class ACL extends Generic\DAOEntity {

}
