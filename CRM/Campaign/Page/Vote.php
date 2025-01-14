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
 * Page for voting tab interface.
 */
class CRM_Campaign_Page_Vote extends CRM_Core_Page {
  private $_surveyId;
  private $_interviewerId;

  /**
   * @return mixed
   */
  public function reserve() {
    //build ajax voter search and selector.
    $controller = new CRM_Core_Controller_Simple('CRM_Campaign_Form_Gotv', ts('Reserve Respondents'));
    $controller->set('votingTab', TRUE);
    $controller->set('subVotingTab', 'searchANDReserve');

    $controller->process();
    return $controller->run();
  }

  /**
   * @return mixed
   */
  public function interview() {
    //build interview and release voter interface.
    $controller = new CRM_Core_Controller_Simple('CRM_Campaign_Form_Task_Interview', ts('Interview Respondents'));
    $controller->set('votingTab', TRUE);
    $controller->set('subVotingTab', 'searchANDInterview');
    if ($this->_surveyId) {
      $controller->set('surveyId', $this->_surveyId);
    }
    if ($this->_interviewerId) {
      $controller->set('interviewerId', $this->_interviewerId);
    }
    $controller->process();
    return $controller->run();
  }

  public function browse() {
    $this->_tabs = [
      'reserve' => ts('Reserve Respondents'),
      'interview' => ts('Interview Respondents'),
    ];

    $this->_surveyId = CRM_Utils_Request::retrieve('sid', 'Positive', $this);
    $this->_interviewerId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    $subPageType = CRM_Utils_Request::retrieve('type', 'String', $this);
    if ($subPageType) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/campaign/vote', "reset=1&subPage={$subPageType}"));
      //load the data in tabs.
      $this->{$subPageType}();
    }
    else {
      //build the tabs.
      $this->buildTabs();
    }
    $this->assign('subPageType', $subPageType);

    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header')
      ->addSetting([
        'tabSettings' => [
          'active' => strtolower($_GET['subPage'] ?? 'reserve'),
        ],
      ]);
  }

  /**
   * @return string
   */
  public function run() {
    $this->browse();

    return parent::run();
  }

  public function buildTabs() {
    $allTabs = [];
    foreach ($this->_tabs as $name => $title) {
      // check for required permissions.
      if (!CRM_Core_Permission::check([
          [
            'manage campaign',
            'administer CiviCampaign',
            "{$name} campaign contacts",
          ],
      ])) {
        continue;
      }

      $urlParams = "type={$name}";
      if ($this->_surveyId) {
        $urlParams .= "&sid={$this->_surveyId}";
      }
      if ($this->_interviewerId) {
        $urlParams .= "&cid={$this->_interviewerId}";
      }
      $allTabs[$name] = [
        'title' => $title,
        'valid' => TRUE,
        'active' => TRUE,
        'link' => CRM_Utils_System::url('civicrm/campaign/vote', $urlParams),
      ];
    }

    $tabs = empty($allTabs) ? [] : \CRM_Core_Smarty::setRequiredTabTemplateKeys($allTabs);
    $this->assign('tabHeader', $tabs);
  }

}
