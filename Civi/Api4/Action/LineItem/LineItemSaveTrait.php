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


namespace Civi\Api4\Action\LineItem;

/**
 * @inheritDoc
 * @method bool getCheckTaxAmount()
 * @method $this setCheckTaxAmount(bool $checkTaxAmount)
 */
trait LineItemSaveTrait {

  /**
   * Optional param to indicate that the tax_amount on the line item should be calculated
   *
   * @var bool
   */
  protected $checkTaxAmount = TRUE;

  /**
   * @inheritDoc
   */
  protected function writeObjects($items) {
    foreach ($items as &$item) {
      $item['check_tax_amount'] = $this->checkTaxAmount;
    }
    return parent::writeObjects($items);
  }

}
