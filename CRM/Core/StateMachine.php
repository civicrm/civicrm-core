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
 * Core StateMachine. All statemachines subclass for the core one
 * for functionality specific to their needs.
 *
 * A statemachine keeps track of differnt states and forms for a
 * html quickform controller.
 *
 */
class CRM_Core_StateMachine {

  /**
   * the controller of this state machine
   * @var object
   */
  protected $_controller;

  /**
   * the list of states that belong to this state machine
   * @var array
   */
  protected $_states;

  /**
   * the list of pages that belong to this state machine. Note
   * that a state and a form have a 1 <-> 1 relationship. so u
   * can always derive one from the other
   * @var array
   */
  protected $_pages;

  /**
   * The names of the pages
   *
   * @var array
   */
  protected $_pageNames;

  /**
   * the mode that the state machine is operating in
   * @var int
   */
  protected $_action = NULL;

  /**
   * The display name for this machine
   * @var string
   */
  protected $_name = NULL;

  /**
   * class constructor
   *
   * @param object $controller the controller for this state machine
   *
   * @return object
   * @access public
   */
  function __construct(&$controller, $action = CRM_Core_Action::NONE) {
    $this->_controller = &$controller;
    $this->_action = $action;

    $this->_states = array();
  }

  /**
   * getter for name
   *
   * @return string
   * @access public
   */
  public function getName() {
    return $this->_name;
  }

  /**
   * setter for name
   *
   * @param string
   *
   * @return void
   * @access public
   */
  public function setName($name) {
    $this->_name = $name;
  }

  /**
   * do a state transition jump. Currently only supported types are
   * Next and Back. The other actions (Cancel, Done, Submit etc) do
   * not need the state machine to figure out where to go
   *
   * @param  object    $page       CRM_Core_Form the current form-page
   * @param  string    $actionName Current action name, as one Action object can serve multiple actions
   * @param  string    $type       The type of transition being requested (Next or Back)
   *
   * @return void
   * @access public
   */
  function perform(&$page, $actionName, $type = 'Next') {
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
   * helper function to add a State to the state machine
   *
   * @param string $name  the internal name
   * @param int    $type  the type of state (START|FINISH|SIMPLE)
   * @param object $prev  the previous page if any
   * @param object $next  the next page if any
   *
   * @return void
   * @access public
   */
  function addState($name, $type, $prev, $next) {
    $this->_states[$name] = new CRM_Core_State($name, $type, $prev, $next, $this);
  }

  /**
   * Given a name find the corresponding state
   *
   * @param string $name the state name
   *
   * @return object the state object
   * @access public
   */
  function find($name) {
    if (array_key_exists($name, $this->_states)) {
      return $this->_states[$name];
    }
    else {
      return NULL;
    }
  }

  /**
   * return the list of state objects
   *
   * @return array array of states in the state machine
   * @access public
   */
  function getStates() {
    return $this->_states;
  }

  /**
   * return the state object corresponding to the name
   *
   * @param string $name name of page
   *
   * @return CRM_Core_State state object matching the name
   * @access public
   */
  function &getState($name) {
    if (isset($this->_states[$name])) {
    return $this->_states[$name];
  }

    /*
     * This is a gross hack for ajax driven requests where
     * we change the form name to allow multiple edits to happen
     * We need a cleaner way of doing this going forward
     */
    foreach ($this->_states as $n => $s ) {
      if (substr($name, 0, strlen($n)) == $n) {
        return $s;
      }
    }

    return null;
  }

  /**
   * return the list of form objects
   *
   * @return array array of pages in the state machine
   * @access public
   */
  function getPages() {
    return $this->_pages;
  }

  /**
   * addSequentialStates: meta level function to create a simple
   * wizard for a state machine that is completely sequential.
   *
   * @access public
   *
   * @param array $states states is an array of arrays. Each element
   * of the top level array describes a state. Each state description
   * includes the name, the display name and the class name
   *
   * @param array $pages (reference ) the array of page objects
   *
   * @return void
   */
  function addSequentialPages(&$pages) {
    $this->_pages = &$pages;
    $numPages = count($pages);

    $this->_pageNames = array();
    foreach ($pages as $tempName => $value) {
      if (CRM_Utils_Array::value('className', $value)) {
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
   * reset the state machine
   *
   * @return void
   * @access public
   */
  function reset() {
    $this->_controller->reset();
  }

  /**
   * getter for action
   *
   * @return int
   * @access public
   */
  function getAction() {
    return $this->_action;
  }

  /**
   * setter for content
   *
   * @param string $content the content generated by this state machine
   *
   * @return void
   * @access public
   */
  function setContent(&$content) {
    $this->_controller->setContent($content);
  }

  /**
   * getter for content
   *
   * @return string
   * @access public
   */
  function &getContent() {
    return $this->_controller->getContent();
  }

  function getDestination() {
    return $this->_controller->getDestination();
  }

  function getSkipRedirection() {
    return $this->_controller->getSkipRedirection();
  }

  function fini() {
    return $this->_controller->fini();
  }

  function cancelAction() {
    return $this->_controller->cancelAction();
  }

  /**
   * Should the controller reset the session
   * In some cases, specifically search we want to remember
   * state across various actions and want to go back to the
   * beginning from the final state, but retain the same session
   * values
   *
   * @return boolean
   */
  function shouldReset() {
    return TRUE;
}

}

