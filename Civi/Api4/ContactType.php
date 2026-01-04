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
 * ContactType entity.
 *
 * With this entity you can create or update any new or existing Contact type or a sub type
 * In case of updating existing ContactType, id of that particular ContactType must
 * be in $params array.
 *
 * Creating a new contact type requires at minimum a label and parent_id.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/contacts/#contact-subtypes
 * @see \Civi\Api4\Contact
 * @searchable secondary
 * @since 5.19
 * @package Civi\Api4
 */
class ContactType extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;
  use Generic\Traits\HierarchicalEntity;

}
