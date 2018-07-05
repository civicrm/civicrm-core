<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class provides the functionality to map
 * the address for group of
 * contacts.
 */
class CRM_Contact_Form_Task_Map extends CRM_Contact_Form_Task {

  /**
   * Are we operating in "single mode", i.e. mapping address to one
   * specific contact?
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive',
      $this, FALSE
    );
    $lid = CRM_Utils_Request::retrieve('lid', 'Positive',
      $this, FALSE
    );
    $eid = CRM_Utils_Request::retrieve('eid', 'Positive',
      $this, FALSE
    );
    $profileGID = CRM_Utils_Request::retrieve('profileGID', 'Integer',
      $this, FALSE
    );
    $this->assign('profileGID', $profileGID);
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    $type = 'Contact';
    if ($cid) {
      $ids = array($cid);
      $this->_single = TRUE;
      if ($profileGID) {
        // this does a check and ensures that the user has permission on this profile
        // CRM-11766
        $profileIDs = CRM_Profile_Page_Listings::getProfileContact($profileGID);
        if (!in_array($cid, $profileIDs)) {
          CRM_Core_Error::fatal();
        }
      }
      elseif ($context) {
        $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);
        $urlParams = 'force=1';
        if (CRM_Utils_Rule::qfKey($qfKey)) {
          $urlParams .= "&qfKey=$qfKey";
        }
        $session = CRM_Core_Session::singleton();
        $urlString = "civicrm/contact/search/$context";
        if ($context == 'search') {
          $urlString = 'civicrm/contact/search';
        }
        $url = CRM_Utils_System::url($urlString, $urlParams);
        $session->replaceUserContext($url);
      }
    }
    elseif ($eid) {
      $ids = $eid;
      $type = 'Event';
    }
    else {
      if ($profileGID) {
        $ids = CRM_Profile_Page_Listings::getProfileContact($profileGID);
      }
      else {
        parent::preProcess();
        $ids = $this->_contactIds;
      }
    }
    self::createMapXML($ids, $lid, $this, TRUE, $type);
    $this->assign('single', $this->_single);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons(array(
        array(
          'type' => 'done',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
  }

  /**
   * Assign smarty variables to the template that will be used by google api to plot the contacts.
   *
   * @param array $ids
   * @param int $locationId
   *   Location_id.
   * @param CRM_Core_Page $page
   * @param bool $addBreadCrumb
   * @param string $type
   */
  public static function createMapXML($ids, $locationId, &$page, $addBreadCrumb, $type = 'Contact') {
    $config = CRM_Core_Config::singleton();

    CRM_Utils_System::setTitle(ts('Map Location(s)'));
    $page->assign('query', 'CiviCRM Search Query');
    $page->assign('mapProvider', $config->mapProvider);
    $page->assign('mapKey', urlencode($config->mapAPIKey));
    if ($type == 'Contact') {
      $imageUrlOnly = FALSE;

      // google needs image url, CRM-6564
      if ($config->mapProvider == 'Google' || $config->mapProvider == 'OpenStreetMaps') {
        $imageUrlOnly = TRUE;
      }
      $locations = CRM_Contact_BAO_Contact_Location::getMapInfo($ids, $locationId, $imageUrlOnly);
    }
    else {
      $locations = CRM_Event_BAO_Event::getMapInfo($ids);
    }

    if (empty($locations)) {
      CRM_Core_Error::statusBounce(ts('This address does not contain latitude/longitude information and cannot be mapped.'));
    }

    if (empty($config->mapProvider)) {
      CRM_Core_Error::statusBounce(ts('You need to configure a Mapping Provider before using this feature (Administer > System Settings > Mapping and Geocoding).'));
    }
    if ($addBreadCrumb) {
      $session = CRM_Core_Session::singleton();
      $redirect = $session->readUserContext();
      if ($type == 'Contact') {
        $bcTitle = ts('Contact');
      }
      else {
        $bcTitle = ts('Event Info');
        $action = CRM_Utils_Request::retrieve('action', 'String',
          $page, FALSE
        );
        if ($action) {
          $args = 'reset=1&action=preview&id=';
        }
        else {
          $args = 'reset=1&id=';
        }
        $session->pushUserContext(CRM_Utils_System::url('civicrm/event/info', "{$args}{$ids}"));
      }
      CRM_Utils_System::appendBreadCrumb($bcTitle, $redirect);
    }

    $page->assign_by_ref('locations', $locations);

    // only issue a javascript warning if we know we will not
    // mess the poor user with too many warnings
    if (count($locations) <= 3) {
      $page->assign('geoCodeWarn', TRUE);
    }
    else {
      $page->assign('geoCodeWarn', FALSE);
    }

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

    $center = array(
      'lat' => (float ) $sumLat / count($locations),
      'lng' => (float ) $sumLng / count($locations),
    );
    $span = array(
      'lat' => (float ) ($maxLat - $minLat),
      'lng' => (float ) ($maxLng - $minLng),
    );
    $page->assign_by_ref('center', $center);
    $page->assign_by_ref('span', $span);
  }

}
