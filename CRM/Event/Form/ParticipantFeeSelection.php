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

/**
 * This form used for changing / updating fee selections for the events
 * event/contribution is partially paid
 *
 */
class CRM_Event_Form_ParticipantFeeSelection extends CRM_Core_Form {
  use CRM_Event_Form_EventFormTrait;
  use CRM_Contact_Form_ContactFormTrait;

  public $useLivePageJS = TRUE;

  /**
   * @var int
   *
   * @deprecated
   */
  protected $_contactId;

  protected $_contributorDisplayName = NULL;

  protected $_contributorEmail = NULL;

  protected $_toDoNotEmail = NULL;

  protected $contributionID;

  protected $fromEmailId = NULL;

  public $_eventId = NULL;

  public $_action = NULL;

  public $_values = NULL;

  /**
   * @var int
   *
   * @deprecated use getParticipantID().
   */
  public $_participantId;

  protected $_paidAmount = NULL;

  public $_isPaidEvent = NULL;

  protected $contributionAmt = NULL;

  private CRM_Financial_BAO_Order $order;

  public function preProcess() {
    $this->_fromEmails = CRM_Event_BAO_Event::getFromEmailIds($this->getEventID());

    if ($this->getContributionID()) {
      $this->_isPaidEvent = TRUE;
    }
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, TRUE);

    //set the payment mode - _mode property is defined in parent class
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);

    [$this->_contributorDisplayName, $this->_contributorEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->getContactID());
    $this->assign('displayName', $this->getContactValue('display_name'));
    $this->assign('email', $this->getContactValue('email_primary.email'));
    $this->assign('contactId', $this->getContactID());
    $this->assign('id', $this->getParticipantID());

    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->_participantId, 'event');
    $this->_paidAmount = $paymentInfo['paid'];
    $this->assign('paymentInfo', $paymentInfo);
    $this->assign('feePaid', $this->_paidAmount);

    $ids = CRM_Event_BAO_Participant::getParticipantIds($this->getContributionID());
    if (count($ids) > 1) {
      $total = CRM_Price_BAO_LineItem::getLineTotal($this->getContributionID());
      $this->assign('totalLineTotal', $total);
      $this->assign('lineItemTotal', $total);
    }

    $title = ts("Change selections for %1", [1 => $this->_contributorDisplayName]);
    if ($title) {
      $this->setTitle($title);
    }
  }

  /**
   * Get the contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionID(): ?int {
    if ($this->contributionID === NULL) {
      $this->contributionID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $this->getParticipantID(), 'contribution_id', 'participant_id') ?: FALSE;

      if (!$this->contributionID) {
        $primaryParticipantID = $this->getParticipantValue('registered_by_id');
        if ($primaryParticipantID) {
          $this->contributionID = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_ParticipantPayment', $primaryParticipantID, 'contribution_id', 'participant_id') ?: FALSE;
        }
      }
    }
    return $this->contributionID ?: NULL;
  }

  protected function getOrder(): CRM_Financial_BAO_Order {
    if (!isset($this->order)) {
      $this->initializeOrder();
    }
    return $this->order;
  }

  protected function initializeOrder(): void {
    $this->order = new CRM_Financial_BAO_Order();
    $this->order->setPriceSetID($this->getPriceSetID());
    $this->order->setIsExcludeExpiredFields($this->_action !== CRM_Core_Action::UPDATE);
    $this->order->setForm($this);
    foreach ($this->getPriceFieldMetaData() as $priceField) {
      if ($priceField['html_type'] === 'Text') {
        $this->submittableMoneyFields[] = 'price_' . $priceField['id'];
      }
    }
  }

  /**
   * Get the form context.
   *
   * This is important for passing to the buildAmount hook as CiviDiscount checks it.
   *
   * @return string
   */
  public function getFormContext(): string {
    return 'event';
  }

  /**
   * Get price field metadata.
   *
   * The returned value is an array of arrays where each array
   * is an id-keyed price field and an 'options' key has been added to that
   * arry for any options.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return array
   */
  public function getPriceFieldMetaData(): array {
    if (!empty($this->_values['fee'])) {
      return $this->_values['fee'];
    }
    if (!empty($this->_priceSet['fields'])) {
      return $this->_priceSet['fields'];
    }
    return $this->order->getPriceFieldsMetadata();
  }

  /**
   * Set default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $params = ['id' => $this->getParticipantID()];

    CRM_Event_BAO_Participant::getValues($params, $defaults, $ids);

    $priceSetValues = $this->getPriceSetDefaults();
    $priceFieldId = (array_keys($this->_values['fee']));
    if (!empty($priceSetValues)) {
      $defaults[$this->_participantId] = array_merge($defaults[$this->_participantId], $priceSetValues);
    }
    else {
      foreach ($priceFieldId as $key => $value) {
        if (!empty($value) && ($this->_values['fee'][$value]['html_type'] == 'Radio' || $this->_values['fee'][$value]['html_type'] == 'Select') && !$this->_values['fee'][$value]['is_required']) {
          $fee_keys = array_keys($this->_values['fee']);
          $defaults[$this->_participantId]['price_' . $fee_keys[$key]] = 0;
        }
      }
    }
    $this->assign('totalAmount', $defaults[$this->_participantId]['fee_amount'] ?? NULL);
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $fee_level = $defaults[$this->_participantId]['fee_level'];
      CRM_Event_BAO_Participant::fixEventLevel($fee_level);
      $this->assign('fee_level', $fee_level);
      $this->assign('fee_amount', $defaults[$this->_participantId]['fee_amount'] ?? NULL);
    }
    $defaults = $defaults[$this->_participantId];
    return $defaults;
  }

  /**
   * This function sets the default values for price set.
   *
   * @return array
   */
  private function getPriceSetDefaults() {
    $defaults = [];
    $participantID = $this->getParticipantID();

    // use line items for setdefault price set fields, CRM-4090
    $lineItems[$participantID] = CRM_Price_BAO_LineItem::getLineItems($participantID, 'participant', FALSE, FALSE);

    if (is_array($lineItems[$participantID]) &&
      !CRM_Utils_System::isNull($lineItems[$participantID])
    ) {

      $priceFields = $htmlTypes = $optionValues = [];
      foreach ($lineItems[$participantID] as $lineId => $items) {
        $priceFieldId = $items['price_field_id'] ?? NULL;
        $priceOptionId = $items['price_field_value_id'] ?? NULL;
        if ($priceFieldId && $priceOptionId) {
          $priceFields[$priceFieldId][] = $priceOptionId;
        }
      }

      if (empty($priceFields)) {
        return $defaults;
      }

      // get all price set field html types.
      $sql = '
SELECT  id, html_type
  FROM  civicrm_price_field
 WHERE  id IN (' . implode(',', array_keys($priceFields)) . ')';
      $fieldDAO = CRM_Core_DAO::executeQuery($sql);
      while ($fieldDAO->fetch()) {
        $htmlTypes[$fieldDAO->id] = $fieldDAO->html_type;
      }

      foreach ($lineItems[$participantID] as $lineId => $items) {
        $fieldId = $items['price_field_id'];
        $htmlType = $htmlTypes[$fieldId] ?? NULL;
        if (!$htmlType) {
          continue;
        }

        if ($htmlType === 'Text') {
          $defaults["price_{$fieldId}"] = $items['qty'];
        }
        else {
          $fieldOptValues = $priceFields[$fieldId] ?? NULL;
          if (!is_array($fieldOptValues)) {
            continue;
          }

          foreach ($fieldOptValues as $optionId) {
            if ($htmlType === 'CheckBox') {
              $defaults["price_{$fieldId}"][$optionId] = TRUE;
            }
            else {
              $defaults["price_{$fieldId}"] = $optionId;
              break;
            }
          }
        }
      }
    }

    return $defaults;
  }

  /**
   * Build form.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {

    $statuses = CRM_Event_PseudoConstant::participantStatus();
    $this->assign('partiallyPaid', array_search('Partially paid', $statuses));
    $this->assign('pendingRefund', array_search('Pending refund', $statuses));
    $this->assign('participantStatus', $this->getParticipantValue('status_id'));

    $this->assign('currencySymbol', CRM_Core_BAO_Country::defaultCurrencySymbol());

    // line items block
    $lineItem = $event = [];
    $params = ['id' => $this->_eventId];
    CRM_Event_BAO_Event::retrieve($params, $event);

    //retrieve custom information
    $this->_values = [];

    $this->_values['line_items'] = CRM_Price_BAO_LineItem::getLineItems($this->_participantId, 'participant');
    $this->_priceSet = $this->getOrder()->getPriceSetMetadata();
    $this->setPriceFieldMetaData($this->order->getPriceFieldsMetadata());
    self::initializeEventFee($this, $this->_action !== CRM_Core_Action::UPDATE, $this->getPriceSetID());
    $this->buildAmount(TRUE, NULL, $this->getPriceSetID());

    if (!CRM_Utils_System::isNull($this->_values['line_items'] ?? NULL)) {
      $lineItem[] = $this->_values['line_items'];
    }
    $this->assign('lineItem', empty($lineItem) ? FALSE : $lineItem);
    $event = CRM_Event_BAO_Event::getEvents(0, $this->_eventId);
    $this->assign('eventName', $event[$this->_eventId]);

    $statusOptions = CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
    $this->add('select', 'status_id', ts('Participant Status'),
      [
        '' => ts('- select -'),
      ] + $statusOptions,
      TRUE
    );

    $this->addElement('checkbox',
      'send_receipt',
      ts('Send Confirmation?'), NULL,
      ['onclick' => "showHideByValue('send_receipt','','notice','table-row','radio',false); showHideByValue('send_receipt','','from-email','table-row','radio',false);"]
    );

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails['from_email_id']);

    $this->add('textarea', 'receipt_text', ts('Confirmation Message'));

    $noteAttributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Note');
    $this->add('textarea', 'note', ts('Notes'), $noteAttributes['note']);

    $buttons[] = [
      'type' => 'upload',
      'name' => ts('Save'),
      'isDefault' => TRUE,
    ];

    if (CRM_Event_BAO_Participant::isPrimaryParticipant($this->_participantId)) {
      $buttons[] = [
        'type' => 'upload',
        'name' => ts('Save and Record Payment'),
        'subName' => 'new',
      ];
    }
    $buttons[] = [
      'type' => 'cancel',
      'name' => ts('Cancel'),
    ];

    $this->addButtons($buttons);
    $this->addFormRule(['CRM_Event_Form_ParticipantFeeSelection', 'formRule'], $this);
  }

  /**
   * Set price field metadata.
   *
   * @param array $metadata
   */
  protected function setPriceFieldMetaData(array $metadata): void {
    $this->_values['fee'] = $this->_priceSet['fields'] = $metadata;
  }

  /**
   * Build the radio/text form elements for the amount field
   *
   * @internal function is not currently called by any extentions in our civi
   * 'universe' and is not supported for such use. Signature has changed & will
   * change again.
   *
   * @param int|null $discountId
   *   Discount id for the event.
   * @param int|null $priceSetID
   *
   * @throws \CRM_Core_Exception
   */
  protected function buildAmount($discountId, $priceSetID) {
    $form = $this;
    $feeFields = $form->_values['fee'] ?? NULL;

    if (is_array($feeFields)) {
      $form->_feeBlock = &$form->_values['fee'];
    }

    //check for discount.
    $discountedFee = $form->_values['discount'] ?? NULL;
    if (is_array($discountedFee) && !empty($discountedFee)) {
      if (!$discountId) {
        $form->_discountId = $discountId = CRM_Core_BAO_Discount::findSet($form->_eventId, 'civicrm_event');
      }
      if ($discountId) {
        $form->_feeBlock = &$form->_values['discount'][$discountId];
      }
    }
    if (!is_array($form->_feeBlock)) {
      $form->_feeBlock = [];
    }

    //its time to call the hook.
    CRM_Utils_Hook::buildAmount('event', $form, $form->_feeBlock);

    //format price set fields across option full.
    $form->formatFieldsForOptionFull();
    // This is probably not required now - normally loaded from event ....
    $form->add('hidden', 'priceSetId', $priceSetID);

    foreach ($form->_feeBlock as $field) {
      $fieldId = $field['id'];
      $elementName = 'price_' . $fieldId;

      $isRequire = $field['is_required'] ?? NULL;

      //user might modified w/ hook.
      $options = $field['options'] ?? NULL;

      if (!is_array($options)) {
        continue;
      }

      $optionFullIds = $field['option_full_ids'] ?? [];

      //soft suppress required rule when option is full.
      if (!empty($optionFullIds) && (count($options) == count($optionFullIds))) {
        $isRequire = FALSE;
      }
      if (!empty($options)) {
        //build the element.
        CRM_Price_BAO_PriceField::addQuickFormElement($form,
          $elementName,
          $fieldId,
          FALSE,
          $isRequire,
          NULL,
          $options,
          $optionFullIds
        );
      }
    }
    $form->_priceSet['id'] ??= $priceSetID;
    $form->assign('priceSet', $form->_priceSet);
  }

  /**
   *
   */
  private function formatFieldsForOptionFull(): void {
    $form = $this;
    $priceSet = $form->get('priceSet');
    $priceSetId = $form->get('priceSetId');
    $defaultPricefieldIds = [];
    if (!empty($form->_values['line_items'])) {
      foreach ($form->_values['line_items'] as $lineItem) {
        $defaultPricefieldIds[] = $lineItem['price_field_value_id'];
      }
    }
    if (!$priceSetId ||
      !is_array($priceSet) ||
      empty($priceSet) || empty($priceSet['optionsMaxValueTotal'])
    ) {
      return;
    }

    //get the current price event price set options count.
    $currentOptionsCount = $this->getPriceOptionCount();
    $recordedOptionsCount = CRM_Event_BAO_Participant::priceSetOptionsCount($form->_eventId, []);
    $optionFullTotalAmount = 0;
    $currentParticipantNo = (int) substr($form->_name, 12);
    foreach ($form->_feeBlock as & $field) {
      $optionFullIds = [];
      if (!is_array($field['options'])) {
        continue;
      }
      foreach ($field['options'] as & $option) {
        $optId = $option['id'];
        $maxValue = $option['max_value'] ?? 0;
        $dbTotalCount = $recordedOptionsCount[$optId] ?? 0;
        $currentTotalCount = $currentOptionsCount[$optId] ?? 0;

        $totalCount = $currentTotalCount + $dbTotalCount;
        $isFull = FALSE;
        if ($maxValue &&
          (($totalCount >= $maxValue) &&
            (empty($form->_lineItem[$currentParticipantNo][$optId]['price_field_id']) || $dbTotalCount >= $maxValue))
        ) {
          $isFull = TRUE;
          $optionFullIds[$optId] = $optId;
          if ($field['html_type'] !== 'Select') {
            if (in_array($optId, $defaultPricefieldIds)) {
              $optionFullTotalAmount += $option['amount'] ?? 0;
            }
          }
          else {
            if (!empty($defaultPricefieldIds) && in_array($optId, $defaultPricefieldIds)) {
              unset($optionFullIds[$optId]);
            }
          }
        }
        $option['is_full'] = $isFull;
        $option['total_option_count'] = $dbTotalCount + $currentTotalCount;
      }

      //finally get option ids in.
      $field['option_full_ids'] = $optionFullIds;
    }
    $form->assign('optionFullTotalAmount', $optionFullTotalAmount);
  }

  /**
   * Get the discount ID.
   *
   * @return int|null
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getDiscountID(): ?int {
    $discountID = (int) CRM_Core_BAO_Discount::findSet($this->getEventID(), 'civicrm_event');
    return $discountID ?: NULL;
  }

  /**
   * @param $fields
   * @param $files
   * @param self $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    return $errors;
  }

  /**
   * Post process form.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    CRM_Price_BAO_LineItem::changeFeeSelections($params, $this->_participantId, 'participant', $this->getContributionID(), $this);
    $this->contributionAmt = CRM_Core_DAO::getFieldValue('CRM_Contribute_BAO_Contribution', $this->getContributionID(), 'total_amount');
    // email sending
    if (!empty($params['send_receipt'])) {
      $fetchParticipantVals = ['id' => $this->_participantId];
      CRM_Event_BAO_Participant::getValues($fetchParticipantVals, $participantDetails);
      $participantParams = array_merge($params, $participantDetails[$this->_participantId]);
      $this->emailReceipt($participantParams);
    }

    // update participant
    CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $this->_participantId, 'status_id', $params['status_id']);
    if (!empty($params['note'])) {
      $noteParams = [
        'entity_table' => 'civicrm_participant',
        'note' => $params['note'],
        'entity_id' => $this->_participantId,
        'contact_id' => $this->_contactId,
      ];
      CRM_Core_BAO_Note::add($noteParams);
    }
    CRM_Core_Session::setStatus(ts("The fee selection has been changed for this participant"), ts('Saved'), 'success');

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('upload', 'new')) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/payment/add',
        "reset=1&action=add&component=event&id={$this->_participantId}&cid={$this->_contactId}"
      ));
    }
  }

  /**
   * @param array $params
   */
  private function emailReceipt(array $params): void {
    $updatedLineItem = CRM_Price_BAO_LineItem::getLineItems($this->_participantId, 'participant', FALSE, FALSE);
    $lineItem = [];
    if ($updatedLineItem) {
      $lineItem[] = $updatedLineItem;
    }
    $this->assign('lineItem', empty($lineItem) ? FALSE : $lineItem);

    // offline receipt sending
    if (array_key_exists($params['from_email_address'], $this->_fromEmails['from_email_id'])) {
      $receiptFrom = $params['from_email_address'];
    }

    $this->assign('module', 'Event Registration');
    //use of the message template below requires variables in different format
    $events = [];
    $returnProperties = ['fee_label', 'start_date', 'end_date', 'is_show_location', 'title'];

    //get all event details.
    CRM_Core_DAO::commonRetrieveAll('CRM_Event_DAO_Event', 'id', $params['event_id'], $events, $returnProperties);
    $event = $events[$params['event_id']];
    unset($event['start_date'], $event['end_date']);

    $role = CRM_Event_PseudoConstant::participantRole();
    $participantRoles = $params['role_id'] ?? NULL;
    if (is_array($participantRoles)) {
      $selectedRoles = [];
      foreach (array_keys($participantRoles) as $roleId) {
        $selectedRoles[] = $role[$roleId];
      }
      $event['participant_role'] = implode(', ', $selectedRoles);
    }
    else {
      $event['participant_role'] = $role[$participantRoles] ?? NULL;
    }
    $event['is_monetary'] = $this->_isPaidEvent;

    if ($params['receipt_text']) {
      $event['confirm_email_text'] = $params['receipt_text'];
    }

    $this->assign('event', $event);

    $this->assign('isShowLocation', $event['is_show_location']);
    if (($event['is_show_location'] ?? NULL) == 1) {
      $locationParams = [
        'entity_id' => $params['event_id'],
        'entity_table' => 'civicrm_event',
      ];
      $location = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
      $this->assign('location', $location);
    }

    if ($this->_isPaidEvent) {
      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      if (!$this->_mode) {
        if (isset($params['payment_instrument_id'])) {
          $this->assign('paidBy', $paymentInstrument[$params['payment_instrument_id']] ?? NULL);
        }
      }

      $this->assign('totalAmount', $this->contributionAmt);
      $this->assign('checkNumber', $params['check_number'] ?? NULL);
    }

    $this->assign('register_date', $params['register_date']);

    // Retrieve the name and email of the contact - this will be the TO for receipt email
    [$this->_contributorDisplayName, $this->_contributorEmail, $this->_toDoNotEmail] = CRM_Contact_BAO_Contact::getContactDetails($this->_contactId);

    $this->_contributorDisplayName = ($this->_contributorDisplayName == ' ') ? $this->_contributorEmail : $this->_contributorDisplayName;

    $waitStatus = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
    $this->assign('isOnWaitlist', (bool) in_array($params['status_id'], $waitStatus));
    $this->assign('contactID', $this->_contactId);

    $sendTemplateParams = [
      'workflow' => 'event_offline_receipt',
      'contactId' => $this->_contactId,
      'isTest' => FALSE,
      'PDFFilename' => ts('confirmation') . '.pdf',
      'modelProps' => [
        'participantID' => $this->_participantId,
        'eventID' => $params['event_id'],
        'contributionID' => $this->getContributionID(),
      ],
    ];

    // try to send emails only if email id is present
    // and the do-not-email option is not checked for that contact
    if ($this->_contributorEmail && !$this->_toDoNotEmail) {
      $sendTemplateParams['from'] = $receiptFrom;
      $sendTemplateParams['toName'] = $this->_contributorDisplayName;
      $sendTemplateParams['toEmail'] = $this->_contributorEmail;
      $sendTemplateParams['cc'] = $this->_fromEmails['cc'] ?? NULL;
      $sendTemplateParams['bcc'] = $this->_fromEmails['bcc'] ?? NULL;
    }

    CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
  }

  /**
   * Get the event ID.
   *
   * This function is supported for use outside of core.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  public function getEventID(): int {
    if (!$this->_eventId) {
      $this->_eventId = $this->getParticipantValue('event_id');
    }
    return $this->_eventId;
  }

  /**
   * Get the price set ID for the event.
   *
   * @return int|null
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getPriceSetID(): ?int {
    if ($this->getDiscountID()) {
      $priceSetID = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Discount', $this->getDiscountID(), 'price_set_id');
    }
    else {
      $priceSetID = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $this->getEventID());
    }
    // Currently some extensions, eg. civi-discount, might look for this. Once we can be
    // sure all financial forms have the api-supported function `getPriceSetID` we can
    // add some noise to attempts to get it & move people over.
    $this->set('priceSetId', $priceSetID);
    return $priceSetID;
  }

  /**
   * Get id of participant being acted on.
   *
   * @return int
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * No exception should be thrown... as it should be unreachable/overridden.
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getParticipantID(): int {
    if (!$this->_participantId) {
      $this->_participantId = (int) CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    }
    return $this->_participantId;
  }

  /**
   * Get the contact ID.
   *
   * Override this for more complex retrieval as required by the form.
   *
   * @return int|null
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getContactID(): ?int {
    if (!$this->_contactId) {
      $this->_contactId = (int) $this->getParticipantValue('contact_id');
    }
    return $this->_contactId;
  }

  /**
   * Initiate event fee.
   *
   * Formerly shared function.
   *
   * @internal function has had several recent signature changes & is expected to be eventually removed.
   */
  private function initializeEventFee(): void {
    $priceSetId = $this->getPriceSetID();
    $form = $this;
    //get the price set fields participant count.
    //get option max value info.
    $optionsMaxValueTotal = 0;

    if (!empty($form->_priceSet['fields'])) {
      foreach ($form->_priceSet['fields'] as $field) {
        foreach ($field['options'] as $option) {
          $maxVal = $option['max_value'] ?? 0;
          $optionsMaxValueTotal += $maxVal;
        }
      }
    }

    $form->_priceSet['optionsMaxValueTotal'] = $optionsMaxValueTotal;

    $form->set('priceSet', $form->_priceSet);

    $eventFee = $form->_values['fee'] ?? NULL;
    if (!is_array($eventFee) || empty($eventFee)) {
      $form->_values['fee'] = [];
    }
  }

  /**
   * Calculate total count for each price set options.
   *
   * - currently selected by user.
   *
   * @return array
   *   array of each option w/ count total.
   */
  private function getPriceOptionCount() {
    $form = $this;
    $params = $form->get('params');
    $priceSet = $form->get('priceSet');
    $priceSetId = $form->get('priceSetId');

    $optionsCount = [];
    if (!$priceSetId ||
      !is_array($priceSet) ||
      empty($priceSet) ||
      !is_array($params) ||
      empty($params)
    ) {
      return $optionsCount;
    }

    $priceSetFields = $priceMaxFieldDetails = [];
    // @todo - replace this line with if ($this->getOrder()->isUseParticipantCount()) {
    // (pending https://github.com/civicrm/civicrm-core/pull/29249 being merged)
    if (!empty($priceSet['optionsCountTotal'])) {
      $priceSetFields = $priceSet['optionsCountDetails']['fields'];
    }

    if (!empty($priceSet['optionsMaxValueTotal'])) {
      $priceMaxFieldDetails = $priceSet['optionsMaxValueDetails']['fields'];
    }

    $addParticipantNum = substr($form->_name, 12);
    foreach ($params as $pCnt => $values) {
      if ($values == 'skip' ||
        $pCnt === $addParticipantNum
      ) {
        continue;
      }

      foreach ($values as $valKey => $value) {
        if (!str_contains($valKey, 'price_')) {
          continue;
        }

        $priceFieldId = substr($valKey, 6);
        if (!$priceFieldId ||
          !is_array($value) ||
          !(array_key_exists($priceFieldId, $priceSetFields) || array_key_exists($priceFieldId, $priceMaxFieldDetails))
        ) {
          continue;
        }

        foreach ($value as $optId => $optVal) {
          if (($priceSet['fields'][$priceFieldId]['html_type'] ?? NULL) === 'Text') {
            $currentCount = $optVal;
          }
          else {
            $currentCount = 1;
          }

          if (isset($priceSetFields[$priceFieldId]) && isset($priceSetFields[$priceFieldId]['options'][$optId])) {
            $currentCount = $priceSetFields[$priceFieldId]['options'][$optId] * $optVal;
          }

          $optionsCount[$optId] = $currentCount + ($optionsCount[$optId] ?? 0);
        }
      }
    }

    return $optionsCount;
  }

}
