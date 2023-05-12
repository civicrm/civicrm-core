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
 * Payment Processor Type entity.
 *
 * @see \Civi\Api4\PaymentProcessor
 *
 * @searchable none
 * @since 5.23
 * @package Civi\Api4
 */
class PaymentProcessorType extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;

}
