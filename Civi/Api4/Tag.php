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
 * Tag entity.
 *
 * Tags in CiviCRM are used for Contacts, Activities, Cases & Attachments.
 * They are connected to those entities via the EntityTag table.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/groups-and-tags/#tags
 * @package Civi\Api4
 */
class Tag extends Generic\DAOEntity {

}
