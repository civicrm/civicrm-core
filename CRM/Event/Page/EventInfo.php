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
 * Event Info Page - Summary about the event
 */
class CRM_Event_Page_EventInfo extends CRM_Core_Page {

  use CRM_Event_Form_EventFormTrait;

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   */
  public function run() {
    $config = CRM_Core_Config::singleton();
    // ensure that the user has permission to see this page
    if (!CRM_Core_Permission::event(CRM_Core_Permission::VIEW,
      $this->getEventID(), 'view event info'
    )
    ) {
      CRM_Utils_System::setUFMessage(ts('You do not have permission to view this event'));
      return CRM_Utils_System::permissionDenied();
    }

    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'register');
    $this->assign('context', $context);

    $this->assign('iCal', CRM_Event_BAO_Event::getICalLinks($this->_id));
    $this->assign('isShowICalIconsInline', TRUE);

    //retrieve event information
    $params = ['id' => $this->_id];
    $values = ['event' => NULL];
    CRM_Event_BAO_Event::retrieve($params, $values['event']);

    if (!$values['event'] || !$values['event']['is_active']) {
      CRM_Utils_System::setUFMessage(ts('The event you requested is currently unavailable (contact the site administrator for assistance).'));
      return CRM_Utils_System::permissionDenied();
    }

    if (!$values['event']['is_public']) {
      CRM_Utils_System::setNoRobotsFlag();
    }

    if (!empty($values['event']['is_template'])) {
      // form is an Event Template
      CRM_Core_Error::statusBounce(ts('The page you requested is currently unavailable.'));
    }

    // Add Event Type to $values in case folks want to display it
    $values['event']['event_type'] = CRM_Utils_Array::value($values['event']['event_type_id'], CRM_Event_PseudoConstant::eventType());

    $this->assign('isShowLocation', $values['event']['is_show_location'] ?? NULL);

    $eventCurrency = CRM_Utils_Array::value('currency', $values['event'], $config->defaultCurrency);
    $this->assign('eventCurrency', $eventCurrency);

    // show event fees.
    if ($this->_id && !empty($values['event']['is_monetary'])) {
      $priceSetId = $this->getPriceSetID();

      // get price set options, - CRM-5209
      if ($priceSetId) {
        $setDetails = CRM_Price_BAO_PriceSet::getSetDetail($priceSetId, TRUE, TRUE);

        $priceSetFields = $setDetails[$priceSetId]['fields'];
        if (is_array($priceSetFields)) {
          $fieldCnt = 1;
          $visibility = CRM_Core_PseudoConstant::visibility('name');

          // CRM-14492 Admin price fields should show up on event registration if user has 'administer CiviCRM' permissions
          $adminFieldVisible = FALSE;
          if (CRM_Core_Permission::check('administer CiviCRM')) {
            $adminFieldVisible = TRUE;
          }

          foreach ($priceSetFields as $fieldValues) {
            if (!is_array($fieldValues['options']) ||
              empty($fieldValues['options']) ||
              (($fieldValues['visibility_id'] ?? NULL) != array_search('public', $visibility) && $adminFieldVisible == FALSE)
            ) {
              continue;
            }

            if (count($fieldValues['options']) > 1) {
              $values['feeBlock']['value'][$fieldCnt] = '';
              $values['feeBlock']['tax_amount'][$fieldCnt] = '';
              $values['feeBlock']['label'][$fieldCnt] = $fieldValues['label'];
              $values['feeBlock']['lClass'][$fieldCnt] = 'price_set_option_group-label';
              $values['feeBlock']['isDisplayAmount'][$fieldCnt] = $fieldValues['is_display_amounts'] ?? NULL;
              $fieldCnt++;
              $labelClass = 'price_set_option-label';
            }
            else {
              $labelClass = 'price_set_field-label';
            }

            foreach ($fieldValues['options'] as $optionId => $optionVal) {
              if (($optionVal['visibility_id'] ?? NULL) != array_search('public', $visibility) &&
                $adminFieldVisible == FALSE
              ) {
                continue;
              }

              $values['feeBlock']['isDisplayAmount'][$fieldCnt] = $fieldValues['is_display_amounts'] ?? NULL;
              if (Civi::settings()->get('invoicing') && isset($optionVal['tax_amount'])) {
                $values['feeBlock']['value'][$fieldCnt] = CRM_Price_BAO_PriceField::getTaxLabel($optionVal, 'amount', $eventCurrency);
                $values['feeBlock']['tax_amount'][$fieldCnt] = $optionVal['tax_amount'];
              }
              else {
                $values['feeBlock']['value'][$fieldCnt] = $optionVal['amount'];
                $values['feeBlock']['tax_amount'][$fieldCnt] = 0;
              }
              $values['feeBlock']['label'][$fieldCnt] = $optionVal['label'];
              $values['feeBlock']['lClass'][$fieldCnt] = $labelClass;
              $fieldCnt++;
            }
          }
        }
        // Tell tpl we have price set fee data and whether it's a quick_config price set
        $this->assign('isPriceSet', 1);
        $this->assign('isQuickConfig', $setDetails[$priceSetId]['is_quick_config']);
      }
    }

    $params = ['entity_id' => $this->_id, 'entity_table' => 'civicrm_event'];
    $values['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);

    // fix phone type labels
    if (!empty($values['location']['phone'])) {
      $phoneTypes = CRM_Core_DAO_Phone::buildOptions('phone_type_id');
      foreach ($values['location']['phone'] as &$val) {
        if (!empty($val['phone_type_id'])) {
          $val['phone_type_display'] = $phoneTypes[$val['phone_type_id']];
        }
      }
    }

    //retrieve custom field information
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Event', NULL, $this->_id, 0, $values['event']['event_type_id'], NULL,
      TRUE, NULL, FALSE, CRM_Core_Permission::VIEW, NULL, TRUE);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $this->_id);
    $this->assign('action', CRM_Core_Action::VIEW);
    //To show the event location on maps directly on event info page
    $locations = CRM_Event_BAO_Event::getMapInfo($this->_id);
    $this->assign('locations', $locations);
    if (!empty($locations) && !empty($values['event']['is_map'])) {
      $this->assign('mapProvider', \Civi::settings()->get('mapProvider'));
      $this->assign('mapKey', \Civi::settings()->get('mapAPIKey'));
      $sumLat = $sumLng = 0;
      $maxLat = $maxLng = -400;
      $minLat = $minLng = 400;
      foreach ($locations as $location) {
        $sumLat += $location['lat'];
        $sumLng += $location['lng'];

        if ($location['lat'] > $maxLat) {
          $maxLat = $location['lat'];
        }
        if ($location['lat'] < $minLat) {
          $minLat = $location['lat'];
        }

        if ($location['lng'] > $maxLng) {
          $maxLng = $location['lng'];
        }
        if ($location['lng'] < $minLng) {
          $minLng = $location['lng'];
        }
      }

      $center = [
        'lat' => (float ) $sumLat / count($locations),
        'lng' => (float ) $sumLng / count($locations),
      ];
      $span = [
        'lat' => (float ) ($maxLat - $minLat),
        'lng' => (float ) ($maxLng - $minLng),
      ];
      $this->assign('center', $center);
      $this->assign('span', $span);
      if ($action == CRM_Core_Action::PREVIEW) {
        $mapURL = CRM_Utils_System::url('civicrm/contact/map/event',
          "eid={$this->_id}&reset=1&action=preview",
          FALSE, NULL, TRUE,
          TRUE
        );
      }
      else {
        $mapURL = CRM_Utils_System::url('civicrm/contact/map/event',
          "eid={$this->_id}&reset=1",
          FALSE, NULL, TRUE,
          TRUE
        );
      }

      $this->assign('skipLocationType', TRUE);
      $this->assign('mapURL', $mapURL);
    }
    $findParticipants = ['statusCounted' => '', 'statusNotCounted' => ''];
    if (CRM_Core_Permission::check('view event participants')) {
      $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1', 'label');
      $statusTypesPending = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 0', 'label');
      $findParticipants['statusCounted'] = implode(', ', array_values($statusTypes));
      $findParticipants['statusNotCounted'] = implode(', ', array_values($statusTypesPending));
    }
    $this->assign('findParticipants', $findParticipants);

    $participantListingID = $values['event']['participant_listing_id'] ?? NULL;
    if ($participantListingID) {
      $participantListingURL = CRM_Utils_System::url('civicrm/event/participant',
        "reset=1&id={$this->_id}",
        FALSE, NULL, TRUE, TRUE
      );
    }
    $this->assign('participantListingURL', $participantListingURL ?? NULL);

    $hasWaitingList = $values['event']['has_waitlist'] ?? NULL;
    $isEventOpenForRegistration = CRM_Event_BAO_Event::validRegistrationRequest($values['event'], $this->_id);

    $allowRegistration = FALSE;
    // check that the user has permission to register for this event
    // Looks like the form shows either way - just not the register option.
    $hasPermission = CRM_Core_Permission::event(CRM_Core_Permission::EDIT,
      $this->getEventID(), 'register for events'
    );
    if ($hasPermission && $this->isAvailableForOnlineRegistration()) {
      // Copy over the checksum, if one was present
      $checksum_query = CRM_Utils_Request::retrieveValue('cs', 'String');
      if ($checksum_query) {
        $checksum_query = '&cs=' . $checksum_query . '&cid=' . CRM_Utils_Request::retrieveValue('cid', 'Positive');
      }

      // we always generate urls for the front end in joomla
      $action_query = $action === CRM_Core_Action::PREVIEW ? "&action=$action" : '';
      $url = CRM_Utils_System::url('civicrm/event/register',
        "id={$this->_id}&reset=1{$action_query}{$checksum_query}",
        FALSE, NULL, TRUE,
        TRUE
      );
      $registerText = ts('Register Now');
      if (!empty($values['event']['registration_link_text'])) {
        $registerText = $values['event']['registration_link_text'];
      }

      //Fixed for CRM-4855
      $allowRegistration = CRM_Event_BAO_Event::showHideRegistrationLink($values);

      $this->assign('registerText', $registerText);
      $this->assign('registerURL', $url);
    }

    $this->assign('registerClosed', !empty($values['event']['is_online_registration']) && !$isEventOpenForRegistration && CRM_Core_Permission::check('register for events'));
    $this->assign('allowRegistration', $allowRegistration);

    $isAlreadyRegistered = $this->isAlreadyRegistered();
    // noFullMsg was originally passed in to suppress the message about the event being full. The intent
    // was originally such that when you were sending the user back to the info page after registering
    // they would not be told it was full. Along the way it got overloaded to encompass
    // the scenario where the user is potentially trying to register another user & hence became very confusing.
    // We could probably make this make more sense by
    // 1) always passing cid in the url if the person is being redirected to register another person
    // and using that rather than the logged in use to check for existing registrations
    // 2) using a more positive 'you are registered' message rather than making it
    // sound like a mistake.
    // 3) using the normal button to pass cid=0 information rather than a link.
    $noFullMsg = CRM_Utils_Request::retrieve('noFullMsg', 'String', $this, FALSE, 'false');
    $isSuppressEventFullMessage = $noFullMsg !== 'false';
    $statusMessage = ($this->isEventFull() && !$isSuppressEventFullMessage && !$isAlreadyRegistered) ? $this->getEventValue('event_full_text') : '';
    if (($this->isEventFull() && $noFullMsg === 'false') || $isAlreadyRegistered) {
      if ($isAlreadyRegistered) {
        // @todo - this usage of `$isSuppressEventFullMessage` is where the historical mis-use makes it
        // confusing - better use of cid in the url would help.
        if (!$isSuppressEventFullMessage) {
          if ($values['event']['allow_same_participant_emails']) {
            $statusMessage = ts('It looks like you are already registered for this event.  You may proceed if you want to create an additional registration.');
          }
          else {
            $registerUrl = CRM_Utils_System::url('civicrm/event/register',
              "reset=1&id={$values['event']['id']}&cid=0"
            );
            $statusMessage = ts("It looks like you are already registered for this event. If you want to change your registration, or you feel that you've gotten this message in error, please contact the site administrator.") . ' ' . ts('You can also <a href="%1">register another participant</a>.', [1 => $registerUrl]);
          }
        }
      }
      elseif ($hasWaitingList) {
        $statusMessage = $values['event']['waitlist_text'] ?? NULL;
        if (!$statusMessage) {
          $statusMessage = ts('Event is currently full, but you can register and be a part of waiting list.');
        }
      }
      if ($isEventOpenForRegistration == 1) {
        CRM_Core_Session::setStatus($statusMessage);
      }
    }
    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);

    // set page title = event title
    CRM_Utils_System::setTitle($values['event']['title']);

    $this->assign('event', $values['event']);
    $this->assign('feeBlock', $values['feeBlock'] ?? NULL);
    $this->assign('location', $values['location']);

    if (CRM_Core_Permission::check(['access CiviEvent', 'edit all events'])) {
      $this->assign('manageEventLinks', CRM_Event_Page_ManageEvent::tabs());
    }

    return parent::run();
  }

  /**
   * Get the selected Event ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return int|null
   */
  public function getEventID(): int {
    if (!isset($this->_id)) {
      try {
        $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
      }
      catch (CRM_Core_Exception $e) {
        CRM_Utils_System::sendInvalidRequestResponse(ts('Missing Event ID'));
      }
      $this->_id = $id;
    }
    return (int) $this->_id;
  }

  /**
   * @return string
   */
  public function getTemplateFileName() {
    if ($this->getEventID()) {
      $templateFile = "CRM/Event/Page/{$this->_id}/EventInfo.tpl";
      $template = CRM_Core_Page::getTemplate();

      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return parent::getTemplateFileName();
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
    if ($this->getEventValue('is_monetary')) {
      //CRM-10434
      $discountID = CRM_Core_BAO_Discount::findSet($this->getEventID(), 'civicrm_event');
      if ($discountID) {
        return (int) CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Discount', $discountID, 'price_set_id');
      }

      return (int) CRM_Price_BAO_PriceSet::getFor('civicrm_event', $this->getEventID());
    }
    return NULL;
  }

  /**
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function isAlreadyRegistered(): bool {
    $params = [
      // @todo - instead of just checking logged in user check for cid=0 or an integer
      // in the url. (For zero there should be no check).
      'contact_id' => CRM_Core_Session::getLoggedInContactID(),
      'event_id' => $this->getEventID(),
      'role_id' => $this->getEventValue('default_role_id'),
    ];
    return CRM_Event_BAO_Event::checkRegistration($params);
  }

}
