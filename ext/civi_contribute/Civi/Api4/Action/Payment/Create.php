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

namespace Civi\Api4\Action\Payment;

use Civi\Api4\CustomField;
use Civi\Api4\Generic\Traits\DAOActionTrait;
use Civi\Api4\Utils\FormattingUtil;

/**
 * This API Action creates a payment and supports setting custom fields on FinancialTrxn
 *
 * @method $this setNotificationForPayment(bool $notificationForPayment) Set whether to disable Notification for Payment
 * @method bool getNotificationForPayment() Get notificationForPayment Param
 * @method $this setnotificationForCompleteOrder(bool $notificationForCompleteOrder) Set whether to disable Notification on complete order
 * @method bool getNotificationForCompleteOrder() Get notificationForCompleteOrder Param
 * @method $this setDisableActionsOnCompleteOrder(bool $disableActionsOnCompleteOrder) Set whether to disable actions on complete order
 * @method bool getDisableActionsOnCompleteOrder() Get disableActionsOnCompleteOrder Param
 *
 */
class Create extends \Civi\Api4\Generic\AbstractCreateAction {

  use DAOActionTrait;
  /**
   * Trigger Notification when Payment is received
   *
   * @var bool
   */
  protected $notificationForPayment = FALSE;

  /**
   * Trigger Notification when Order is completed
   *
   * @var bool
   */
  protected $notificationForCompleteOrder = TRUE;

  /**
   * Disable actions such as triggering membership renewal on Complete Order
   *
   * @var bool
   */
  protected $disableActionsOnCompleteOrder = FALSE;

  public static function getCreateFields() {
    // Basically a copy of _civicrm_api3_payment_create_spec;
    $fields = [
      [
        'name' => 'contribution_id',
        'required' => TRUE,
        'description' => ts('Contribution ID'),
        'data_type' => 'Integer',
        'fk_entity' => 'Contribution',
        'input_type' => 'EntityRef',
      ],
      [
        'name' => 'total_amount',
        'required' => TRUE,
        'description' => ts('Total Payment Amount'),
        'data_type' => 'Float',
      ],
      [
        'name' => 'fee_amount',
        'description' => ts('Fee Amount'),
        'data_type' => 'Float',
      ],
      [
        'name' => 'payment_processor_id',
        'data_type' => 'Integer',
        'description' => ts('Payment Processor for this payment'),
        'fk_entity' => 'PaymentProcessor',
        'input_type' => 'EntityRef',
      ],
      [
        'name' => 'trxn_date',
        'description' => ts('Payment Date'),
        'data_type' => 'Datetime',
        'default_value' => 'now',
        'required' => TRUE,
      ],
      [
        'name' => 'payment_instrument_id',
        'data_type' => 'Integer',
        'description' => ts('Payment Method (FK to payment_instrument option group values)'),
        'pseudoconstant' => [
          'optionGroupName' => 'payment_instrument',
          'optionEditPath' => 'civicrm/admin/options/payment_instrument',
        ],
      ],
      [
        'name' => 'card_type_id',
        'data_type' => 'Integer',
        'description' => ts('Card Type ID (FK to accept_creditcard option group values)'),
        'pseudoconstant' => [
          'optionGroupName' => 'accept_creditcard',
          'optionEditPath' => 'civicrm/admin/options/accept_creditcard',
        ],
      ],
      [
        'name' => 'trxn_result_code',
        'data_type' => 'String',
        'description' => ts('Transaction Result Code'),
      ],
      [
        'name' => 'trxn_id',
        'data_type' => 'String',
        'description' => ts('Transaction ID supplied by external processor. This may not be unique.'),
      ],
      [
        'name' => 'order_reference',
        'data_type' => 'String',
        'description' => ts('Payment Processor external order reference'),
      ],
      [
        'name' => 'check_number',
        'data_type' => 'String',
        'description' => ts('Check Number'),
      ],
      [
        'name' => 'pan_truncation',
        'type' => 'String',
        'description' => ts('PAN Truncation (Last 4 digits of credit card)'),
      ],
    ];
    $customFields = CustomField::get(FALSE)
      ->addSelect('custom_group_id:name', 'name', 'label', 'data_type')
      ->addWhere('custom_group_id.extends', '=', 'FinancialTrxn')
      ->execute();
    foreach ($customFields as $customField) {
      $customField['name'] = $customField['custom_group_id:name'] . '.' . $customField['name'];
      unset($customField['id'], $customField['custom_group_id:name']);
      $customField['description'] = $customField['label'];
      $fields[] = $customField;
    }
    return $fields;
  }

  public function fields(): array {
    return self::getCreateFields();
  }

  /**
   *
   * Note that the result class is that of the annotation below, not the h
   * in the method (which must match the parent class)
   *
   * @var \Civi\Api4\Generic\Result $result
   */
  public function _run(\Civi\Api4\Generic\Result $result) {
    $this->values['is_send_contribution_notification'] = $this->notificationForCompleteOrder;
    $this->formatWriteValues($this->values);
    $fields = $this->entityFields();
    foreach ($fields as $name => $field) {
      if (!isset($params[$name]) && !empty($field['default_value'])) {
        $params[$name] = $field['default_value'];
      }
    }
    $this->validateValues();
    $trxn = \CRM_Financial_BAO_Payment::create($this->values, $this->disableActionsOnCompleteOrder);
    $savedRecords = [];
    $savedRecords[] = $this->baoToArray($trxn, $this->values);
    \CRM_Utils_API_HTMLInputCoder::singleton()->decodeRows($savedRecords);
    FormattingUtil::formatOutputValues($savedRecords, $this->entityFields());
    $result->exchangeArray($savedRecords);
  }

}
