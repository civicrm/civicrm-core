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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
use Civi\Api4\PCPBlock;

/**
 * This class generates form components for processing Event
 */
class CRM_Event_Form_Registration_ThankYou extends CRM_Event_Form_Registration {

  /**
   * Set variables up before form is built.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    parent::preProcess();
    $this->_params = $this->get('params');
    $this->_lineItem = $this->get('lineItem');
    $finalAmount = $this->get('finalAmount');
    $this->assign('finalAmount', $finalAmount);
    $participantInfo = $this->get('participantInfo');
    $this->assign('part', $this->get('part'));
    $this->assign('participantInfo', $participantInfo);
    $customGroup = $this->get('customProfile');
    $this->assign('customProfile', $customGroup);
    $this->assign('individual', $this->get('individual'));

    CRM_Event_Form_Registration_Confirm::assignProfiles($this);

    $this->setTitle($this->_values['event']['thankyou_title'] ?? NULL);
  }

  /**
   * Overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   */
  public function getAction(): int {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }

    return CRM_Core_Action::VIEW;
  }

  /**
   * Build the form object.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    // Assign the email address from a contact id lookup as in CRM_Event_BAO_Event->sendMail()
    $primaryContactId = $this->get('primaryContactId');
    $email = NULL;
    if ($primaryContactId) {
      $email = CRM_Utils_Array::valueByRegexKey('/^email-/', current($this->_params));
      if (!$email) {
        $email = CRM_Contact_BAO_Contact::getPrimaryEmail($primaryContactId);
      }
    }
    $this->assign('email', $email ?? NULL);
    $this->assign('eventConfirmText', $this->getEventValue('is_monetary') ? $this->getPaymentProcessorObject()->getText('eventConfirmText', []) : '');
    $this->assign('eventConfirmEmailText', ($email && $this->getEventValue('is_monetary')) ? $this->getPaymentProcessorObject()->getText('eventConfirmEmailText', ['email' => $email]) : '');

    $this->assignToTemplate();

    $invoicing = \Civi::settings()->get('invoicing');
    $taxAmount = 0;

    $lineItemForTemplate = [];
    if (!empty($this->_lineItem) && is_array($this->_lineItem)) {
      foreach ($this->_lineItem as $key => $value) {
        if (!empty($value) && $value !== 'skip') {
          $lineItemForTemplate[$key] = $value;
          if ($invoicing) {
            foreach ($value as $v) {
              if (isset($v['tax_amount']) || isset($v['tax_rate'])) {
                $taxAmount += $v['tax_amount'];
              }
            }
          }
        }
      }
    }

    if ($this->_priceSetId &&
      !CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config') &&
      !empty($lineItemForTemplate)
    ) {
      $this->assignLineItemsToTemplate($lineItemForTemplate);
    }

    if ($invoicing) {
      $this->assign('totalTaxAmount', $taxAmount);
    }
    $this->assign('totalAmount', $this->get('totalAmount'));

    $hookDiscount = $this->get('hookDiscount');
    if ($hookDiscount) {
      $this->assign('hookDiscount', $hookDiscount);
    }

    $this->assign('receive_date', $this->get('receiveDate'));
    $this->assign('trxn_id', $this->get('trxnId'));
    $this->assign('isAmountzero', $this->get('totalAmount') <= 0);

    $this->assign('defaultRole', FALSE);
    if (($this->_params[0]['defaultRole'] ?? NULL) == 1) {
      $this->assign('defaultRole', TRUE);
    }
    $defaults = [];
    $fields = [];
    if (!empty($this->_fields)) {
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }
    }
    $fields['state_province'] = $fields['country'] = $fields['email'] = 1;
    foreach ($fields as $name => $dontCare) {
      if (isset($this->_params[0][$name])) {
        $defaults[$name] = $this->_params[0][$name];
        if (str_starts_with($name, 'custom_')) {
          $timeField = "{$name}_time";
          if (isset($this->_params[0][$timeField])) {
            $defaults[$timeField] = $this->_params[0][$timeField];
          }
        }
        elseif (in_array($name, CRM_Contact_BAO_Contact::$_greetingTypes)
          && !empty($this->_params[0][$name . '_custom'])
        ) {
          $defaults[$name . '_custom'] = $this->_params[0][$name . '_custom'];
        }
      }
    }

    $this->_submitValues = array_merge($this->_submitValues, $defaults);
    $this->setDefaults($defaults);

    $params['entity_id'] = $this->_eventId;
    $params['entity_table'] = 'civicrm_event';

    $data = [];
    $friendURL = NULL;

    if (function_exists('tellafriend_civicrm_config')) {
      CRM_Friend_BAO_Friend::retrieve($params, $data);
      if (!empty($data['is_active'])) {
        $friendText = $data['title'];
        $this->assign('friendText', $friendText);
        if ($this->_action & CRM_Core_Action::PREVIEW) {
          $friendURL = CRM_Utils_System::url('civicrm/friend',
            "eid={$this->_eventId}&reset=1&action=preview&pcomponent=event"
          );
        }
        else {
          $friendURL = CRM_Utils_System::url('civicrm/friend',
            "eid={$this->_eventId}&reset=1&pcomponent=event"
          );
        }
      }
    }

    $this->assign('friendURL', $friendURL);
    $this->assign('iCal', CRM_Event_BAO_Event::getICalLinks($this->_eventId));
    $this->assign('isShowICalIconsInline', TRUE);

    $this->freeze();

    //lets give meaningful status message, CRM-4320.
    $isOnWaitlist = $isRequireApproval = FALSE;
    if ($this->_allowWaitlist && !$this->_allowConfirmation) {
      $isOnWaitlist = TRUE;
    }
    if ($this->_requireApproval && !$this->_allowConfirmation) {
      $isRequireApproval = TRUE;
    }
    $this->assign('isOnWaitlist', $isOnWaitlist);
    $this->assign('isRequireApproval', $isRequireApproval);
    $this->assign('pcpLink', $this->getPCPBlockID() ? CRM_Utils_System::url('civicrm/contribute/campaign', 'action=add&reset=1&pageId=' . $this->getEventID() . '&component=event') : NULL);
    $this->assign('pcpLinkText', $this->getPCPBlockID() ? $this->getPCPBlockValue('link_text') : NULL);

    // Assign Participant Count to Lineitem Table
    $this->assign('pricesetFieldsCount', CRM_Price_BAO_PriceSet::getPricesetCount($this->_priceSetId));

    // can we blow away the session now to prevent hackery
    $this->controller->reset();
  }

  /**
   * Process the form submission.
   *
   *
   * @return void
   */
  public function postProcess(): void {
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle(): string {
    return ts('Thank You Page');
  }

  /**
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getPCPBlockID(): ?int {
    if (!$this->isDefined('PCPBlock')) {
      $pcpBlock = PCPBlock::get(FALSE)
        ->addWhere('entity_table', '=', 'civicrm_event')
        ->addWhere('entity_id', '=', $this->getEventID())
        ->addWhere('is_active', '=', TRUE)
        ->execute()->first();
      if (!$pcpBlock) {
        return NULL;
      }
      $this->define('PCPBlock', 'PCPBlock', $pcpBlock);
    }
    return $this->lookup('PCPBlock', 'id');
  }

  /**
   * Get a PCP Block value.
   *
   * @param string $value
   *
   * @return mixed|null
   * @throws \CRM_Core_Exception
   * @todo - this should probably be on a trait & made public like similar getValue functions.
   */
  protected function getPCPBlockValue(string $value) {
    if (!$this->getPCPBlockID()) {
      return NULL;
    }
    return $this->lookup('PCPBlock', $value);
  }

}
