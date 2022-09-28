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
 * Financial Trxn entity.
 *
 * Financial transactions are low level accounting entries.
 * They include, but are not limited to, payments.
 *
 * If your interest is really in payments you should use that api.
 *
 * @see https://docs.civicrm.org/dev/en/latest/financial/financialentities/#financial-transactions
 *
 * @searchable secondary
 * @since 5.40
 * @package Civi\Api4
 */
class FinancialTrxn extends Generic\DAOEntity {
  use Generic\Traits\ReadOnlyEntity;

}
