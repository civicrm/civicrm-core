<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * Core StateMachine.
 *
 * All state machines subclass for the core one for functionality specific to their needs.
 *
 * A state machine keeps track of different states and forms for a
 * html quickform controller.
 */
class CRM_Core_StateMachine {

  /**
   * The controller of this state machine.
   * @var object
   */
  protected $_controller;

  /**
   * The list of states that belong to this state machine.
   * @var array
   */
  protected $_states;

  /**
   * The list of pages that belong to this state machine. Note
   * that a state and a form have a 1 <-> 1 relationship. so u
   * can always derive one from the other
   * @var array
   */
  protected $_pages;

  /**
   * The names of the pages.
   *
   * @var array
   */
  protected $_pageNames;

  /**
   * The mode that the state machine is operating in.
   * @var int
   */
  protected $_action = NULL;

  /**
   * The display name for this machine.
   * @var string
   */
  protected $_name = NULL;

  /**
   * Class constructor.
   *
   * @param object $controller
   *   The controller for this state machine.
   *
   * @param \const|int $action
   *
   * @return \CRM_Core_StateMachine
   */
  public function __construct(&$controller, $action = CRM_Core_Action::NONE) {
    $this->_controller = &$controller;
    $this->_action = $action;

    $this->_states = array();
  }

  /**
   * Getter for name.
   *
   * @return string
   */
  public function getName() {
    return $this->_name;
  }

  /**
   * Setter for name.
   *
   * @param string $name
   */
  public function setName($name) {
    $this->_name = $name;
  }

  /**
   * Do a state transition jump.
   *
   * Currently only supported types are
   * Next and Back. The other actions (Cancel, Done, Submit etc) do
   * not need the state machine to figure out where to go
   *
   * @param CRM_Core_Form $page
   *   The current form-page.
   * @param string $actionName
   *   Current action name, as one Action object can serve multiple actions.
   * @param string $type
   *   The type of transition being requested (Next or Back).
   *
   * @return object
   */
  public function perform(&$page, $actionName, $type = 'Next') {
    // save the form values and validation status to the session
    $page->isFormBuilt() or $page->buildForm();

    $pageName = $page->getAttribute('name');
    $data = &$page->controller->container();

    $data['values'][$pageName] = $page->exportValues();
    $data['valid'][$pageName] = $page->validate();

    // if we are going to the next state
    // Modal form and page is invalid: don't go further
    if ($type == 'Next' && !$data['valid'][$pageName]) {
      return $page->handle('display');
    }

    $state = &$this->_states[$pageName];

    // dont know how or why we landed here so abort and display
    // current page
    if (empty($state)) {
      return $page->handle('display');
    }

    // the page is valid, process it if we are jumping to the next state
    if ($type == 'Next') {
      $page->mainProcess();
      // we get the state again, since postProcess might have changed it
      // this bug took me forever to find :) Lobo
      $state = &$this->_states[$pageName];
      $state->handleNextState($page);
    }
    else {
      $state->handleBackState($page);
    }
  }

  /**
   * Helper function to add a State to the state machine.
   *
   * @param string $name
   *   The internal name.
   * @param int $type
   *   The type of state (START|FINISH|SIMPLE).
   * @param object $prev
   *   The previous page if any.
   * @param object $next
   *   The next page if any.
   */
  public function addState($name, $type, $prev, $next) {
    $this->_states[$name] = new CRM_Core_State($name, $type, $prev, $next, $this);
  }

  /**
   * Given a name find the corresponding state.
   *
   * @param string $name
   *   The state name.
   *
   * @return object
   *   the state object
   */
  public function find($name) {
    if (array_key_exists($name, $this->_states)) {
      return $this->_states[$name];
    }
    else {
      return NULL;
    }
  }

  /**
   * Return the list of state objects.
   *
   * @return array
   *   array of states in the state machine
   */
  public function getStates() {
    return $this->_states;
  }

  /**
   * Return the state object corresponding to the name.
   *
   * @param string $name
   *   Name of page.
   *
   * @return CRM_Core_State
   *   state object matching the name
   */
  public function &getState($name) {
    if (isset($this->_states[$name])) {
      return $this->_states[$name];
    }

    /*
     * This is a gross hack for ajax driven requests where
     * we change the form name to allow multiple edits to happen
     * We need a cleaner way of doing this going forward
     */
    foreach ($this->_states as $n => $s) {
      if (substr($name, 0, strlen($n)) == $n) {
        return $s;
      }
    }

    return NULL;
  }

  /**
   * Return the list of form objects.
   *
   * @return array
   *   array of pages in the state machine
   */
  public function getPages() {
    return $this->_pages;
  }

  /**
   * Add sequential pages.
   *
   * Meta level function to create a simple wizard for a state machine that is completely sequential.
   *
   * @param array $pages
   *   (reference ) the array of page objects.
   */
  public function addSequentialPages(&$pages) {
    $this->_pages = &$pages;
    $numPages = count($pages);

    $this->_pageNames = array();
    foreach ($pages as $tempName => $value) {
      if (!empty($value['className'])) {
        $this->_pageNames[] = $tempName;
      }
      else {
        $this->_pageNames[] = CRM_Utils_String::getClassName($tempName);
      }
    }

    $i = 0;
    foreach ($pages as $tempName => $value) {
      $name = $this->_pageNames[$i];

      $className = CRM_Utils_Array::value('className',
        $value,
        $tempName
      );
      $classPath = str_replace('_', '/', $className) . '.php';
      if ($numPages == 1) {
        $prev = $next = NULL;
        $type = CRM_Core_State::START | CRM_Core_State::FINISH;
      }
      elseif ($i == 0) {
        // start state
        $prev = NULL;
        $next = $this->_pageNames[$i + 1];
        $type = CRM_Core_State::START;
      }
      elseif ($i == $numPages - 1) {
        // finish state
        $prev = $this->_pageNames[$i - 1];
        $next = NULL;
        $type = CRM_Core_State::FINISH;
      }
      else {
        // in between simple state
        $prev = $this->_pageNames[$i - 1];
        $next = $this->_pageNames[$i + 1];
        $type = CRM_Core_State::SIMPLE;
      }

      $this->addState($name, $type, $prev, $next);

      $i++;
    }
  }

  /**
   * Reset the state machine.
   */
  public function reset() {
    $this->_controller->reset();
  }

  /**
   * Getter for action.
   *
   * @return int
   */
  public function getAction() {
    return $this->_action;
  }

  /**
   * Setter for content.
   *
   * @param string $content
   *   The content generated by this state machine.
   */
  public function setContent(&$content) {
    $this->_controller->setContent($content);
  }

  /**
   * Getter for content.
   *
   * @return string
   */
  public function &getContent() {
    return $this->_controller->getContent();
  }

  /**
   * @return mixed
   */
  public function getDestination() {
    return $this->_controller->getDestination();
  }

  /**
   * @return mixed
   */
  public function getSkipRedirection() {
    return $this->_controller->getSkipRedirection();
  }

  /**
   * @return mixed
   */
  public function fini() {
    return $this->_controller->fini();
  }

  /**
   * @return mixed
   */
  public function cancelAction() {
    return $this->_controller->cancelAction();
  }

  /**
   * Should the controller reset the session
   * In some cases, specifically search we want to remember
   * state across various actions and want to go back to the
   * beginning from the final state, but retain the same session
   * values
   *
   * @return bool
   */
  public function shouldReset() {
    return TRUE;
  }

}
