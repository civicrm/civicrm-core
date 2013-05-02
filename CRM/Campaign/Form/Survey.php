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
 * This class generates form components for processing a survey
 *
 */
class CRM_Campaign_Form_Survey extends CRM_Core_Form {

  /**
   * The id of the object being edited
   *
   * @var int
   */
  protected $_surveyId;

  /**
   * action
   *
   * @var int
   */
  protected $_action;

  /**
   * surveyTitle
   *
   * @var string
   */
  protected $_surveyTitle;

  public function preProcess() {
    if (!CRM_Campaign_BAO_Campaign::accessCampaign()) {
      CRM_Utils_System::permissionDenied();
    }

    $this->_action   = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add', 'REQUEST');
    $this->_surveyId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    if ($this->_surveyId) {
      $this->_single = TRUE;

      $params = array('id' => $this->_surveyId);
      CRM_Campaign_BAO_Survey::retrieve($params, $surveyInfo);
      $this->_surveyTitle = $surveyInfo['title'];
      $this->assign('surveyTitle', $this->_surveyTitle);
      CRM_Utils_System::setTitle(ts('Configure Survey - %1', array(1 => $this->_surveyTitle)));
    }

    $this->assign('action', $this->_action);
    $this->assign('surveyId', $this->_surveyId);

    // CRM-11480, CRM-11682
    // Preload libraries required by the "Questions" tab
    CRM_UF_Page_ProfileEditor::registerProfileScripts();
    CRM_UF_Page_ProfileEditor::registerSchemas(array('IndividualModel', 'ActivityModel'));

    CRM_Campaign_Form_Survey_TabHeader::build($this);
  }

  /**
   * Function to actually build the form
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $session = CRM_Core_Session::singleton();
    if ($this->_surveyId) {
      $buttons = array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'upload',
          'name' => ts('Save and Done'),
          'subName' => 'done',
        ),
        array(
          'type' => 'upload',
          'name' => ts('Save and Next'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'subName' => 'next',
        ),
      );
    }
    else {
      $buttons = array(
        array(
          'type' => 'upload',
          'name' => ts('Continue >>'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
      );
    }
    $buttons[] =
      array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
            );
    $this->addButtons($buttons);

    $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
    $session->replaceUserContext($url);
  }

  function endPostProcess() {
    // make submit buttons keep the current working tab opened.
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $tabTitle = $className = CRM_Utils_String::getClassName($this->_name);
      if ($tabTitle == 'Main') {
        $tabTitle = 'Main settings';
      }
      $subPage   = strtolower($className);
      CRM_Core_Session::setStatus(ts("'%1' have been saved.", array(1 => $tabTitle)), ts('Saved'), 'success');

      $this->postProcessHook();

      if ($this->_action & CRM_Core_Action::ADD)
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/survey/configure/questions",
                                                         "action=update&reset=1&id={$this->_surveyId}"));

      if ($this->controller->getButtonName('submit') == "_qf_{$className}_upload_done") {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey'));
      }
      else if ($this->controller->getButtonName('submit') == "_qf_{$className}_upload_next") {
        $subPage = CRM_Campaign_Form_Survey_TabHeader::getNextTab($this);
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/survey/configure/{$subPage}",
                                                         "action=update&reset=1&id={$this->_surveyId}"));
      }
      else {
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/survey/configure/{$subPage}",
                                                         "action=update&reset=1&id={$this->_surveyId}"));
      }
    }
  }

  function getTemplateFileName() {
    if ($this->controller->getPrint() == CRM_Core_Smarty::PRINT_NOFORM ||
      $this->getVar('_surveyId') <= 0 ) {
      return parent::getTemplateFileName();
    }
    else {
      // hack lets suppress the form rendering for now
      self::$_template->assign('isForm', FALSE);
      return 'CRM/Campaign/Form/Survey/Tab.tpl';
    }
  }
}

