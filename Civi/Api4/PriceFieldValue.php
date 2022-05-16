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
 * PriceFieldValue entity.
 *
 * @searchable secondary
 * @orderBy weight
 * @groupWeightsBy price_field_id
 * @since 5.27
 * @package Civi\Api4
 */
class PriceFieldValue extends Generic\DAOEntity {
  use Generic\Traits\SortableEntity;

}
