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
 * Activity entity.
 *
 * This entity adds record of any scheduled or completed interaction with one or more contacts.
 * Each activity record is tightly linked to other CiviCRM constituents. With this API you can manually
 * create an activity of desired type for your organisation or any other contact.
 *
 * Creating a new Activity requires at minimum a activity_type_id, entity ID and object_table
 *
 * An activity is a record of some type of interaction with one or more contacts.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/activities/
 * @searchable primary
 * @since 5.19
 * @iconField activity_type_id:icon
 * @parentField parent_id
 * @package Civi\Api4
 */
class Activity extends Generic\DAOEntity {
  use Generic\Traits\HierarchicalEntity;

}
