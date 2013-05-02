<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */
class ICIRR_Form_Profile {
  function buildProfile(&$form, $profileId = NULL) {
    // get the activity type
    $activityTypeId = CRM_Utils_Request::retrieve('acttype', 'Positive', $form);
    $activityName   = CRM_Core_OptionGroup::getLabel('activity_type', $activityTypeId);
    $activityName   = str_replace(' ', '_', $activityName);

    if (!$activityTypeId) {
      return;
    }

    // check if there is associated profile
    $profileId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $activityName, 'id', 'name');

    if (!$profileId) {
      return;
    }

    // get profile fields
    require_once 'CRM/Core/BAO/UFGroup.php';
    $form->_profileFields = CRM_Core_BAO_UFGroup::getFields($profileId, FALSE, CRM_Core_Action::VIEW);

    $form->addElement('hidden', 'profile_id', $profileId);

    // build profile
    foreach ($form->_profileFields as $name => $field) {
      $profileTitle = $field['groupTitle'];
      CRM_Core_BAO_UFGroup::buildProfile($form, $field, NULL);
    }

    $form->assign('profileTitle', $profileTitle);
    $form->assign('profileFields', $form->_profileFields);

    // set defaults in profile
    self::setProfileDefaults($form->_profileFields, $form);
  }

  function setProfileDefaults(&$fields, &$form) {
    $defaults = array();
    $activityId = CRM_Utils_Request::retrieve('activity_id', 'Positive', $form);
    if ($activityId) {
      // get the target contact id
      $contactId = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_ActivityTarget', $activityId, 'target_contact_id', 'activity_id');
      if ($contactId) {
        CRM_Core_BAO_UFGroup::setProfileDefaults($contactId, $fields, $profileDefaults, FALSE);
        foreach ($profileDefaults as $key => $value) {
          $index = substr(str_replace("field[$contactId][", '', $key), 0, -1);
          $defaults[$index] = $value;
        }
        $form->setDefaults($defaults);
      }
    }
  }
}

