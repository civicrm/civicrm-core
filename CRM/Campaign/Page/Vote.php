<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 --------------------------------------------------------------------+
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
 * Page for voting tab interface.
 */
class CRM_Campaign_Page_Vote extends CRM_Core_Page {
  private $_surveyId;
  private $_interviewerId;
  
  function reserve() {
    //build ajax voter search and selector.
    $controller = new CRM_Core_Controller_Simple('CRM_Campaign_Form_Gotv', ts('Reserve Respondents'));
    $controller->set('votingTab', TRUE);
    $controller->set('subVotingTab', 'searchANDReserve');

    $controller->process();
    return $controller->run();
  }

  function interview() {
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

  function browse() {
    $this->_tabs = array('reserve' => ts('Reserve Respondents'),
      'interview' => ts('Interview Respondents'),
    );

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

    //give focus to proper tab.
    $this->assign('selectedTabIndex', array_search(CRM_Utils_Array::value('subPage', $_GET, 'reserve'),
        array_keys($this->_tabs)
      ));
  }

  function run() {
    $this->browse();

    return parent::run();
  }

  function buildTabs() {
    //check for required permissions.
    $superUser = FALSE;
    if (CRM_Core_Permission::check('manage campaign') ||
      CRM_Core_Permission::check('administer CiviCampaign')
    ) {
      $superUser = TRUE;
    }

    $allTabs = array();
    foreach ($this->_tabs as $name => $title) {
      if (!$superUser &&
        !CRM_Core_Permission::check("{$name} campaign contacts")
      ) {
        continue;
      }

      $urlParams = "type={$name}&snippet=1";
      if ($this->_surveyId) {
        $urlParams .= "&sid={$this->_surveyId}";
      }
      if ($this->_interviewerId) {
        $urlParams .= "&cid={$this->_interviewerId}";
      }
      $allTabs[] = array(
        'id' => $name,
        'title' => $title,
        'url' => CRM_Utils_System::url('civicrm/campaign/vote',
          $urlParams
        ),
      );
    }

    $this->assign('allTabs', empty($allTabs) ? FALSE : $allTabs);
  }
}

