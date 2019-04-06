<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class is to build the form for deleting a Survey.
 */
class CRM_Campaign_Form_Survey_Delete extends CRM_Core_Form {

  /**
   * The id of the object being deleted
   *
   * @var int
   */
  protected $_surveyId;

  /**
   * SurveyTitle
   *
   * @var string
   */
  protected $_surveyTitle;


  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    if (!CRM_Campaign_BAO_Campaign::accessCampaign()) {
      CRM_Utils_System::permissionDenied();
    }

    $this->_surveyId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    $params = ['id' => $this->_surveyId];
    CRM_Campaign_BAO_Survey::retrieve($params, $surveyInfo);
    $this->_surveyTitle = $surveyInfo['title'];
    $this->assign('surveyTitle', $this->_surveyTitle);
    CRM_Utils_System::setTitle(ts('Delete Survey') . ' - ' . $this->_surveyTitle);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Delete'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Process the form when submitted.
   */
  public function postProcess() {
    if ($this->_surveyId) {
      CRM_Campaign_BAO_Survey::del($this->_surveyId);
      CRM_Core_Session::setStatus('', ts("'%1' survey has been deleted.", [1 => $this->_surveyTitle]), 'success');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey'));
    }
    else {
      CRM_Core_Error::fatal(ts('Delete action is missing expected survey ID.'));
    }
  }

}
