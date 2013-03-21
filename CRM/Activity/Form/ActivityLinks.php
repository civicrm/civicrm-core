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
 * This class generates form components for Activity Links
 *
 */
class CRM_Activity_Form_ActivityLinks extends CRM_Core_Form {
  public function buildQuickForm() {
    self::commonBuildQuickForm($this);
  }

  static function commonBuildQuickForm($self) {
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $self);
    if (!$contactId) {
      $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, $_REQUEST);
    }
    $urlParams = "action=add&reset=1&cid={$contactId}&selectedChild=activity&atype=";

    $activityTypes = $urls = array();

    $emailTypeId = CRM_Core_OptionGroup::getValue('activity_type',
      'Email',
      'name'
    );

    $letterTypeId = CRM_Core_OptionGroup::getValue('activity_type',
      'Print PDF Letter',
      'name'
    );
    $SMSId = CRM_Core_OptionGroup::getValue('activity_type',
      'Text Message (SMS)',
      'label'
    );

    if (CRM_Utils_Mail::validOutBoundMail() && $contactId) {
      list($name, $email, $doNotEmail, $onHold, $isDeseased) = CRM_Contact_BAO_Contact::getContactDetails($contactId);
      if (!$doNotEmail && $email && !$isDeseased) {
        $activityTypes = array($emailTypeId => ts('Send an Email'));
      }
    }

    if ($contactId && CRM_SMS_BAO_Provider::activeProviderCount()) {
      // Check for existence of a mobile phone and ! do not SMS privacy setting
      $mobileTypeID = CRM_Core_OptionGroup::getValue('phone_type', 'Mobile', 'name');
      list($name, $phone, $doNotSMS) = CRM_Contact_BAO_Contact_Location::getPhoneDetails($contactId, $mobileTypeID);

      if (!$doNotSMS && $phone) {
        $sendSMS = array($SMSId  => ts('Send SMS'));
        $activityTypes += $sendSMS;
      }
    }
    // this returns activity types sorted by weight
    $otherTypes = CRM_Core_PseudoConstant::activityType(FALSE);

    $activityTypes += $otherTypes;

    foreach (array_keys($activityTypes) as $typeId) {
      if ($typeId == $emailTypeId) {
        $urls[$typeId] = CRM_Utils_System::url('civicrm/activity/email/add',
          "{$urlParams}{$typeId}", FALSE, NULL, FALSE
        );
      }
       elseif ($typeId == $SMSId) {
        $urls[$typeId] = CRM_Utils_System::url('civicrm/activity/sms/add',
          "{$urlParams}{$typeId}", FALSE, NULL, FALSE
        );
        }
      elseif ($typeId == $letterTypeId) {
        $urls[$typeId] = CRM_Utils_System::url('civicrm/activity/pdf/add',
          "{$urlParams}{$typeId}", FALSE, NULL, FALSE
        );
      }
      else {
        $urls[$typeId] = CRM_Utils_System::url('civicrm/activity/add',
          "{$urlParams}{$typeId}", FALSE, NULL, FALSE
        );
      }
    }

    $self->assign('activityTypes', $activityTypes);
    $self->assign('urls', $urls);

    $self->assign('suppressForm', TRUE);
  }
}

