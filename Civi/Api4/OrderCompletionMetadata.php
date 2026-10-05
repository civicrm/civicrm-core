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
 * Free-form metadata attached to a contribution, or to one of its line
 * items, to be consumed when that contribution's order/payment completes.
 *
 * Attach against the contribution alone (no line_item_id) for data that
 * applies to the whole order, such as receipt/email overrides. Attach
 * against a specific line_item_id for data specific to that line, such as
 * an explicit end date to apply to the membership it represents, overriding
 * whatever CiviCRM would otherwise calculate.
 *
 * The metadata format is deliberately free-form (a JSON blob), since the set
 * of things it needs to carry is expected to grow. By convention, top-level
 * keys so far are 'entity' (for the entity - eg. membership - the line item
 * represents) and 'email' (for receipt/email overrides).
 *
 * @since 6.20
 * @package Civi\Api4
 */
class OrderCompletionMetadata extends Generic\DAOEntity {

}
