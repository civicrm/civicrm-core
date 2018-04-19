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
 * The basic state element. Each state element is linked to a form and
 * represents the form in the transition diagram. We use the state to
 * determine what action to take on various user input. Actions include
 * things like going back / stepping forward / process etc
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Core_State {

  /**
   * State name.
   * @var string
   */
  protected $_name;

  /**
   * This is a combination "OR" of the STATE_* constants defined below
   * @var int
   */
  protected $_type;

  /**
   * The state that precedes this state.
   * @var CRM_Core_State
   */
  protected $_back;

  /**
   * The state that succeeds this state.
   * @var CRM_Core_State
   */
  protected $_next;

  /**
   * The state machine that this state is part of.
   * @var CRM_Core_StateMachine
   */
  protected $_stateMachine;

  /**
   * The different types of states. As we flush out the framework more
   * we will introduce other conditional / looping states which will
   * bring in more complexity to the framework. For now, lets keep it simple
   * @var int
   */
  const START = 1, FINISH = 2, SIMPLE = 4;

  /**
   * Constructor.
   *
   * @param string $name
   *   Internal name of the state.
   * @param int $type
   *   State type.
   * @param CRM_Core_State $back
   *   State that precedes this state.
   * @param CRM_Core_State $next
   *   State that follows this state.
   * @param CRM_Core_StateMachine $stateMachine
   *   Statemachine that this states belongs to.
   *
   * @return CRM_Core_State
   */
  public function __construct($name, $type, $back, $next, &$stateMachine) {
    $this->_name = $name;
    $this->_type = $type;
    $this->_back = $back;
    $this->_next = $next;

    $this->_stateMachine = &$stateMachine;
  }

  public function debugPrint() {
    CRM_Core_Error::debug("{$this->_name}, {$this->_type}", "{$this->_back}, {$this->_next}");
  }

  /**
   * Given an CRM Form, jump to the previous page.
   *
   * @param CRM_Core_Page $page
   *
   * @return mixed
   *   does a jump to the back state
   */
  public function handleBackState(&$page) {
    if ($this->_type & self::START) {
      $page->handle('display');
    }
    else {
      $back = &$page->controller->getPage($this->_back);
      return $back->handle('jump');
    }
  }

  /**
   * Given an CRM Form, jump to the next page.
   *
   * @param CRM_Core_Page $page
   *
   * @return mixed
   *   Does a jump to the nextstate
   */
  public function handleNextState(&$page) {
    if ($this->_type & self::FINISH) {
      $page->handle('process');
    }
    else {
      $next = &$page->controller->getPage($this->_next);
      return $next->handle('jump');
    }
  }

  /**
   * Determine the name of the next state.
   *
   * This is useful when we want to display the navigation labels or potential path.
   *
   * @return string
   */
  public function getNextState() {
    if ($this->_type & self::FINISH) {
      return NULL;
    }
    else {
      $next = &$page->controller->getPage($this->_next);
      return $next;
    }
  }

  /**
   * Mark this page as valid for the QFC framework.
   *
   * @param array $data
   */
  public function validate(&$data) {
    $data['valid'][$this->_name] = TRUE;
  }

  /**
   * Mark this page as invalid for the QFC framework.
   *
   * @param array $data
   */
  public function invalidate(&$data) {
    $data['valid'][$this->_name] = NULL;
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
   * Getter for type.
   *
   * @return int
   */
  public function getType() {
    return $this->_type;
  }

}
