<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Event Info Page - Summmary about the event
 */
class CRM_Event_Page_EventInfo extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    //get the event id.
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $config = CRM_Core_Config::singleton();
    // ensure that the user has permission to see this page
    if (!CRM_Core_Permission::event(CRM_Core_Permission::VIEW,
        $this->_id
      )) {
      CRM_Utils_System::setUFMessage(ts('You do not have permission to view this event'));
      return CRM_Utils_System::permissionDenied();
    }

    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'register');
    $this->assign('context', $context);

    // Sometimes we want to suppress the Event Full msg
    $noFullMsg = CRM_Utils_Request::retrieve('noFullMsg', 'String', $this, FALSE, 'false');

    // set breadcrumb to append to 2nd layer pages
    $breadCrumbPath = CRM_Utils_System::url('civicrm/event/info',
      "id={$this->_id}&reset=1"
    );
    $additionalBreadCrumb = "<a href=\"$breadCrumbPath\">" . ts('Events') . '</a>';

    //retrieve event information
    $params = array('id' => $this->_id);
    CRM_Event_BAO_Event::retrieve($params, $values['event']);

    if (!$values['event']['is_active']) {
      // form is inactive, die a fatal death
      CRM_Core_Error::fatal(ts('The page you requested is currently unavailable.'));
    }

    if (!empty($values['event']['is_template'])) {
      // form is an Event Template
      CRM_Core_Error::fatal(ts('The page you requested is currently unavailable.'));
    }

    $this->assign('isShowLocation', CRM_Utils_Array::value('is_show_location', $values['event']));

    // show event fees.
    if ($this->_id && CRM_Utils_Array::value('is_monetary', $values['event'])) {
      //CRM-6907
      $config = CRM_Core_Config::singleton();
      $config->defaultCurrency = CRM_Utils_Array::value('currency',
        $values['event'],
        $config->defaultCurrency
      );

      //CRM-10434
      $discountId= CRM_Core_BAO_Discount::findSet($this->_id, 'civicrm_event');
      if ($discountId) {
        $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Discount', $discountId, 'price_set_id');
      } else {
        $priceSetId = CRM_Price_BAO_Set::getFor('civicrm_event', $this->_id);
      }

      // get price set options, - CRM-5209
      if ($priceSetId) {
        $setDetails = CRM_Price_BAO_Set::getSetDetail($priceSetId, TRUE, TRUE);

        $priceSetFields = $setDetails[$priceSetId]['fields'];
        if (is_array($priceSetFields)) {
          $fieldCnt = 1;
          $visibility = CRM_Core_PseudoConstant::visibility('name');

          foreach ($priceSetFields as $fid => $fieldValues) {
            if (!is_array($fieldValues['options']) ||
              empty($fieldValues['options']) ||
              CRM_Utils_Array::value('visibility_id', $fieldValues) != array_search('public', $visibility)
            ) {
              continue;
            }

            if (count($fieldValues['options']) > 1) {
              $values['feeBlock']['value'][$fieldCnt] = '';
              $values['feeBlock']['label'][$fieldCnt] = $fieldValues['label'];
              $values['feeBlock']['lClass'][$fieldCnt] = 'price_set_option_group-label';
              $values['feeBlock']['isDisplayAmount'][$fieldCnt] = CRM_Utils_Array::value('is_display_amounts', $fieldValues);
              $fieldCnt++;
              $labelClass = 'price_set_option-label';
            }
            else {
              $labelClass = 'price_set_field-label';
            }

            foreach ($fieldValues['options'] as $optionId => $optionVal) {
              $values['feeBlock']['isDisplayAmount'][$fieldCnt] = CRM_Utils_Array::value('is_display_amounts', $fieldValues);
              $values['feeBlock']['value'][$fieldCnt] = $optionVal['amount'];
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

    $params = array('entity_id' => $this->_id, 'entity_table' => 'civicrm_event');
    $values['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);

    //retrieve custom field information
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Event', $this, $this->_id, 0, $values['event']['event_type_id']);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree);
    $this->assign('action', CRM_Core_Action::VIEW);
    //To show the event location on maps directly on event info page
    $locations = CRM_Event_BAO_Event::getMapInfo($this->_id);
    if (!empty($locations) && CRM_Utils_Array::value('is_map', $values['event'])) {
      $this->assign('locations', $locations);
      $this->assign('mapProvider', $config->mapProvider);
      $this->assign('mapKey', $config->mapAPIKey);
      $sumLat = $sumLng = 0;
      $maxLat = $maxLng = -400;
      $minLat = $minLng = + 400;
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

      $center = array('lat' => (float ) $sumLat / count($locations),
        'lng' => (float ) $sumLng / count($locations),
      );
      $span = array('lat' => (float )($maxLat - $minLat),
        'lng' => (float )($maxLng - $minLng),
      );
      $this->assign_by_ref('center', $center);
      $this->assign_by_ref('span', $span);
      if ($action == CRM_Core_Action::PREVIEW) {
        $mapURL = CRM_Utils_System::url('civicrm/contact/map/event',
          "eid={$this->_id}&reset=1&action=preview",
          TRUE, NULL, TRUE,
          TRUE
        );
      }
      else {
        $mapURL = CRM_Utils_System::url('civicrm/contact/map/event',
          "eid={$this->_id}&reset=1",
          TRUE, NULL, TRUE,
          TRUE
        );
      }

      $this->assign('skipLocationType', TRUE);
      $this->assign('mapURL', $mapURL);
    }

    if (CRM_Core_Permission::check('view event participants') &&
      CRM_Core_Permission::check('view all contacts')
    ) {
      $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
      $statusTypesPending = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 0');
      $findParticipants['statusCounted'] = implode(', ', array_values($statusTypes));
      $findParticipants['statusNotCounted'] = implode(', ', array_values($statusTypesPending));
      $this->assign('findParticipants', $findParticipants);
    }

    $participantListingID = CRM_Utils_Array::value('participant_listing_id', $values['event']);
    if ($participantListingID) {
      $participantListingURL = CRM_Utils_System::url('civicrm/event/participant',
        "reset=1&id={$this->_id}",
        TRUE, NULL, TRUE, TRUE
      );
      $this->assign('participantListingURL', $participantListingURL);
    }

    $hasWaitingList = CRM_Utils_Array::value('has_waitlist', $values['event']);
    $eventFullMessage = CRM_Event_BAO_Participant::eventFull($this->_id,
      FALSE,
      $hasWaitingList
    );

    $allowRegistration = FALSE;
    if (CRM_Utils_Array::value('is_online_registration', $values['event'])) {
      if (CRM_Event_BAO_Event::validRegistrationRequest($values['event'], $this->_id)) {
        // we always generate urls for the front end in joomla
        $action_query = $action === CRM_Core_Action::PREVIEW ? "&action=$action" : '';
        $url = CRM_Utils_System::url('civicrm/event/register',
          "id={$this->_id}&reset=1{$action_query}",
          TRUE, NULL, TRUE,
          TRUE
        );
        if (!$eventFullMessage || $hasWaitingList) {
          $registerText = ts('Register Now');
          if (CRM_Utils_Array::value('registration_link_text', $values['event'])) {
            $registerText = $values['event']['registration_link_text'];
          }

          // check if we're in shopping cart mode for events
          $enable_cart = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::EVENT_PREFERENCES_NAME,
            'enable_cart'
          );

          if ($enable_cart) {
            $link = CRM_Event_Cart_BAO_EventInCart::get_registration_link($this->_id);
            $registerText = $link['label'];

            $url = CRM_Utils_System::url($link['path'], $link['query'] . $action_query, TRUE, NULL, TRUE, TRUE);
          }

          //Fixed for CRM-4855
          $allowRegistration = CRM_Event_BAO_Event::showHideRegistrationLink($values);

          $this->assign('registerText', $registerText);
          $this->assign('registerURL', $url);
          $this->assign('eventCartEnabled', $enable_cart);
        }
      }
      elseif (CRM_Core_Permission::check('register for events')) {
        $this->assign('registerClosed', TRUE);
      }
    }

    $this->assign('allowRegistration', $allowRegistration);

    $session = CRM_Core_Session::singleton();
    $params = array('contact_id' => $session->get('userID'),
      'event_id' => CRM_Utils_Array::value('id', $values['event']),
      'role_id' => CRM_Utils_Array::value('default_role_id', $values['event']),
    );

    if ($eventFullMessage && ($noFullMsg == 'false') || CRM_Event_BAO_Event::checkRegistration($params)) {
      $statusMessage = $eventFullMessage;
      if (CRM_Event_BAO_Event::checkRegistration($params)) {
        if ($noFullMsg == 'false') {
          if ($values['event']['allow_same_participant_emails']) {
            $statusMessage = ts('It looks like you are already registered for this event.  You may proceed if you want to create an additional registration.');
          }
          else {
            $registerUrl = CRM_Utils_System::url('civicrm/event/register',
              "reset=1&id={$values['event']['id']}&cid=0"
            );
            $statusMessage = ts("It looks like you are already registered for this event. If you want to change your registration, or you feel that you've gotten this message in error, please contact the site administrator.") . ' ' . ts('You can also <a href="%1">register another participant</a>.', array(1 => $registerUrl));
          }
        }
      }
      elseif ($hasWaitingList) {
        $statusMessage = CRM_Utils_Array::value('waitlist_text', $values['event']);
        if (!$statusMessage) {
          $statusMessage = ts('Event is currently full, but you can register and be a part of waiting list.');
        }
      }

      CRM_Core_Session::setStatus($statusMessage);
    }
    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);

    // set page title = event title
    CRM_Utils_System::setTitle($values['event']['title']);

    $this->assign('event', $values['event']);
    if (isset($values['feeBlock'])) {
      $this->assign('feeBlock', $values['feeBlock']);
    }
    $this->assign('location', $values['location']);

    return parent::run();
  }

  function getTemplateFileName() {
    if ($this->_id) {
      $templateFile = "CRM/Event/Page/{$this->_id}/EventInfo.tpl";
      $template = CRM_Core_Page::getTemplate();

      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return parent::getTemplateFileName();
  }
}

