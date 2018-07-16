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
 * This class generates form components for Activity Links.
 */
class CRM_Activity_Form_ActivityLinks extends CRM_Core_Form {
  public function buildQuickForm() {
    self::commonBuildQuickForm($this);
  }

  /**
   * @param $self
   */
  public static function commonBuildQuickForm($self) {
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $self);
    if (!$contactId) {
      $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, $_REQUEST);
    }
    $urlParams = "action=add&reset=1&cid={$contactId}&selectedChild=activity&atype=";

    $allTypes = CRM_Utils_Array::value('values', civicrm_api3('OptionValue', 'get', array(
      'option_group_id' => 'activity_type',
      'is_active' => 1,
      'options' => array('limit' => 0, 'sort' => 'weight'),
    )));

    $activityTypes = array();

    foreach ($allTypes as $act) {
      $url = 'civicrm/activity/add';
      if ($act['name'] == 'Email') {
        if (!CRM_Utils_Mail::validOutBoundMail() || !$contactId) {
          continue;
        }
        list($name, $email, $doNotEmail, $onHold, $isDeceased) = CRM_Contact_BAO_Contact::getContactDetails($contactId);
        if (!$doNotEmail && $email && !$isDeceased) {
          $url = 'civicrm/activity/email/add';
          $act['label'] = ts('Send an Email');
        }
        else {
          continue;
        }
      }
      elseif ($act['name'] == 'SMS') {
        if (!$contactId || !CRM_SMS_BAO_Provider::activeProviderCount() || !CRM_Core_Permission::check('send SMS')) {
          continue;
        }
        // Check for existence of a mobile phone and ! do not SMS privacy setting
        try {
          $phone = civicrm_api3('Phone', 'getsingle', array(
            'contact_id' => $contactId,
            'phone_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'phone_type_id', 'Mobile'),
            'return' => array('phone', 'contact_id'),
            'options' => array('limit' => 1, 'sort' => "is_primary DESC"),
            'api.Contact.getsingle' => array(
              'id' => '$value.contact_id',
              'return' => 'do_not_sms',
            ),
          ));
        }
        catch (CiviCRM_API3_Exception $e) {
          continue;
        }
        if (!$phone['api.Contact.getsingle']['do_not_sms'] && $phone['phone']) {
          $url = 'civicrm/activity/sms/add';
        }
        else {
          continue;
        }
      }
      elseif ($act['name'] == 'Print PDF Letter') {
        $url = 'civicrm/activity/pdf/add';
      }
      elseif (!empty($act['filter']) || (!empty($act['component_id']) && $act['component_id'] != '1')) {
        continue;
      }
      $act['url'] = CRM_Utils_System::url($url,
        "{$urlParams}{$act['value']}", FALSE, NULL, FALSE
      );
      $act += array('icon' => 'fa-plus-square-o');
      $activityTypes[$act['value']] = $act;
    }

    $self->assign('activityTypes', $activityTypes);

    $self->assign('suppressForm', TRUE);
  }

}
