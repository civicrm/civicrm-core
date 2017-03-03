<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
    $defaults = array();
    $date = CRM_Contribute_BAO_Contribution::checkContributeSettings('prior_financial_period');
    if (!empty($date)) {
      $date = strtotime('+1 month', strtotime(date('01-m-Y', strtotime($date))));
    }
    else {
      $date = strtotime("-1 month", strtotime(date('01-m-Y')));
    }
    $defaults['closing_date'] = array(
      'M' => date('n', $date),
      'Y' => date('Y', $date),
    );
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->add('date', 'closing_date', ts('Accounting Period to Close'), CRM_Core_SelectValues::date(NULL, 'M Y', 2, 5), TRUE);
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
    $this->addFormRule(array('CRM_Contribute_Form_CloseAccPeriod', 'formRule'), $this);
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
    $error = array();
    $previousPriorFinPeriod = CRM_Contribute_BAO_Contribution::checkContributeSettings('prior_financial_period');
    if (!empty($previousPriorFinPeriod)) {
      $priorFinPeriod = self::buildClosingDate($fields['closing_date']);
      if (strtotime($previousPriorFinPeriod) >= $priorFinPeriod) {
        $error['closing_date'] = ts('Closing Accounting Period Date cannot be less than prior Closing Accounting Period Date.');
      }
    }
    return $error;
  }

  /**
   * Function to create Closing date based on Month and Year.
   *
   * @param array $closingDate
   *
   */
  public static function buildClosingDate(&$closingDate) {
    $priorFinPeriod = date('Ymt', mktime(0, 0, 0, $closingDate['M'], 1, $closingDate['Y']));
    $priorFinPeriod = strtotime($priorFinPeriod);
    return $priorFinPeriod;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    // Set closing date
    $priorFinPeriod = self::buildClosingDate($params['closing_date']);
    Civi::settings()->set('closing_date', $params['closing_date']);
    $priorFinPeriod = date('m/d/Y', $priorFinPeriod);
    // Create activity
    $activityType = CRM_Core_OptionGroup::getValue('activity_type',
      'Close Accounting Period',
      'name'
    );
    $previousPriorFinPeriod = CRM_Contribute_BAO_Contribution::checkContributeSettings('prior_financial_period');
    $closingDate =  date('Y-m-d', strtotime($priorFinPeriod));
    $activityParams = array(
      'source_contact_id' => CRM_Core_Session::singleton()->get('userID'),
      'assignee_contact_id' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', CRM_Core_Config::domainID(), 'contact_id'),
      'activity_type_id' => $activityType,
      'subject' => ts('Close Accounting Period : ') . $closingDate,
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
        'Completed',
        'name'
      ),
      'activity_date_time' => date('YmdHis'),
      'details' => ts('Trial Balance Report ' . (empty($previousPriorFinPeriod) ? 'for All Time Prior' : "From {$previousPriorFinPeriod}") . " To {$priorFinPeriod}."),
    );
    $fileName = CRM_Core_BAO_FinancialTrxn::createTrialBalanceExport();
    if ($fileName) {
      $activityParams['attachFile_1'] = array(
        'uri' => $fileName,
        'type' => 'text/csv',
        'upload_date' => date('YmdHis'),
        'location' => $fileName,
        'cleanName' => 'TrialBalanceReport_' . $closingDate . '.csv',
      );
    }
    $activity = CRM_Activity_BAO_Activity::create($activityParams);
    // Set Prior Financial Period
    $updateField['prior_financial_period'] = $priorFinPeriod;
    CRM_Contribute_BAO_Contribution::checkContributeSettings(NULL, $updateField);
    CRM_Core_Session::setStatus(ts("Accounting Period has been closed successfully!"), ts('Success'), 'success');
    CRM_Core_Session::singleton()->replaceUserContext(CRM_Utils_System::url('civicrm/activity',
      "action=view&reset=1&id={$activity->id}&atype={$activityType}&cid={$activityParams['source_contact_id']}"
    ));
  }

}
