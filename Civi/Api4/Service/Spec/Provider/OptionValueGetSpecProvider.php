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

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class OptionValueGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $field = new FieldSpec('payment_instrument_fields', $spec->getEntity(), 'Array');
    $field->setLabel(ts('Payment Instrument Fields'))
      ->setTitle(ts('Payment Instrument Fields'))
      ->setColumnName('grouping')
      ->setDescription(ts('Back-office payment fields (e.g. check_number, card_type_id) to show for this payment instrument, decoded from the grouping column.'))
      ->setType('Extra')
      ->setReadonly(TRUE)
      ->addOutputFormatter([__CLASS__, 'formatPaymentInstrumentFields']);
    $spec->addFieldSpec($field);
  }

  public static function formatPaymentInstrumentFields(&$value): void {
    $value = (array) json_decode((string) $value, TRUE);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'OptionValue' && $action === 'get';
  }

}
