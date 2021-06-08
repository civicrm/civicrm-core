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
 */

namespace Civi\Api4;

/**
 * Payment Token entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/contributions/payment-processors/#managing-recurring-contributions
 *
 * @searchable secondary
 * @package Civi\Api4
 */
class PaymentToken extends Generic\DAOEntity {
  use Generic\Traits\OptionList;

}
