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
 * Payment (pseudo) entity.
 *
 * Payments are a subset of financial transactions that specifically relate to
 * actual payments going in or out.
 *
 * @see https://docs.civicrm.org/dev/en/latest/financial/financialentities/#financial-transactions
 *
 * @package Civi\Api4
 */
class Payment extends FinancialTrxn {

}
