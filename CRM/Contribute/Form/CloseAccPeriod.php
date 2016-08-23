<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but   |
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
 * This class generates form components for closing an account period.
 */
class CRM_Contribute_Form_CloseAccPeriod extends CRM_Core_Form {

  /**
   * Set default values.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $period = array();
    $period = Civi::settings()->get('closing_date');
    if (empty($period)) {
      $prior = CRM_Contribute_BAO_Contribution::checkContributeSettings('prior_financial_period');
    }
    else {
      $defaults['closing_date'] = $period;
      return $defaults;
    }
    if (!empty($prior)) {
      $period = array(
        'M' => date('n', strtotime($prior)),
        'd' => date('j', strtotime($prior)),
      );
      if ($period['M'] == 1) {
        $period['M'] = 12;
      }
      else {
        $period['M']--;
      }
      $defaults['closing_date'] = $period;
    }
    else {
      $defaults['closing_date'] = array(
        'M' => date('n', strtotime("-1 month")),
        'd' => date('j'),
      );
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->add('date', 'closing_date', ts('Accounting Period to Close'), CRM_Core_SelectValues::date(NULL, 'M d'), TRUE);
    $confirmClose = ts('Are you sure you want to close accounting period?');
    $this->addButtons(array(
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
        array(
          'type' => 'upload',
          'name' => ts('Close Accounting Period'),
          'js' => array('onclick' => 'return confirm(\'' . $confirmClose . '\');'),
        ),
      )
    );
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param $self
   *
   */
  public static function formRule($fields, $files, $self) {
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    // Create activity
    $activityType = CRM_Core_OptionGroup::getValue('activity_type',
      'Close Accounting Period',
      'name'
    );
    $activityParams = array(
      'source_contact_id' => CRM_Core_Session::singleton()->get('userID'),
      'assignee_contact_id' => CRM_Core_Session::singleton()->get('userID'),
      'activity_type_id' => $activityType,
      'subject' => ts('Close Accounting Period'),
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
        'Completed',
        'name'
      ),
      'activity_date_time' => date('YmdHis'),
    );
    CRM_Activity_BAO_Activity::create($activityParams);
    // Set Prior Financial Period
    $priorFinPeriod = $params['closing_date']['M'] . '/' . $params['closing_date']['d'] . '/' . date('Y');
    Civi::settings()->set('prior_financial_period', date('m/d/Y', strtotime($priorFinPeriod)));
    // Set closing date
    Civi::settings()->set('closing_date', $params['closing_date']);
    CRM_Core_Session::setStatus(ts("Accounting Period has been closed successfully!"), ts('Success'), 'success');
  }

}
