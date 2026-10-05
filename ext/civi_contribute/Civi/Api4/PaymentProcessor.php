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
 * Payment Processor entity.
 *
 * @see https://docs.civicrm.org/sysadmin/en/latest/setup/payment-processors/
 *
 * @since 5.23
 * @package Civi\Api4
 */
class PaymentProcessor extends Generic\DAOEntity {

  /**
   * @see \Civi\Api4\Action\PaymentProcessor\Refund
   * @return array
   */
  public static function permissions(): array {
    return [
      'refund' => ['refund contributions'],
    ] + parent::permissions();
  }

  /**
   * @param bool $checkPermissions
   *
   * @return Action\PaymentProcessor\Refund
   */
  public static function refund(bool $checkPermissions = TRUE): Action\PaymentProcessor\Refund {
    return (new Action\PaymentProcessor\Refund(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
