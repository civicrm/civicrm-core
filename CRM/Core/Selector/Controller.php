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
 * This class is a generic class to be used when we want to display
 * a list of rows along with a set of associated actions
 *
 * Centralizing this code enables us to write a generic lister and enables
 * us to automate the export process. To use this class, the object has to
 * implement the Selector/Api.interface.php class
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */
class CRM_Core_Selector_Controller {

  /**
   * Constants to determine if we should store
   * the output in the session or template
   * @var int
   */
  // move the values from the session to the template
  const SESSION = 1, TEMPLATE = 2,
    TRANSFER = 4, EXPORT = 8, SCREEN = 16, PDF = 32;

  /**
   * A CRM Object that implements CRM_Core_Selector_API.
   * @var object
   */
  protected $_object;

  /**
   * @var CRM_Utils_Sort
   */
  protected $_sort;

  /**
   * The current column to sort on
   * @var int
   */
  protected $_sortID;

  /**
   * The sortOrder array
   * @var array
   */
  protected $_sortOrder;

  /**
   * @var CRM_Utils_Pager
   */
  protected $_pager;

  /**
   * The pageID
   * @var int
   */
  protected $_pageID;

  /**
   * Offset
   * @var int
   */
  protected $_pagerOffset;

  /**
   * Number of rows to return
   * @var int
   */
  protected $_pagerRowCount;

  /**
   * Total number of rows
   * @var int
   */
  protected $_total;

  /**
   * The objectAction for the WebObject
   */
  protected $_action;

  /**
   * This caches the content for the display system
   *
   * @var string
   */
  protected $_content;

  /**
   * Is this object being embedded in another object. If
   * so the display routine needs to not do any work. (The
   * parent object takes care of the display)
   *
   * @var bool
   */
  protected $_embedded = FALSE;

  /**
   * Are we in print mode? if so we need to modify the display
   * functionality to do a minimal display :)
   *
   * @var bool
   */
  protected $_print = FALSE;

  /**
   * The storage object (typically a form or a page)
   *
   * @var Object
   */
  protected $_store;

  /**
   * Output target, session, template or both?
   *
   * @var int
   */
  protected $_output;

  /**
   * Prefif for the selector variables
   *
   * @var int
   */
  protected $_prefix;

  /**
   * Cache the smarty template for efficiency reasons
   *
   * @var CRM_Core_Smarty
   */
  public static $_template;

  /**
   * Array of properties that the controller dumps into the output object
   *
   * @var array
   */
  public static $_properties = ['columnHeaders', 'rows', 'rowsEmpty'];

  /**
   * Should we compute actions dynamically (since they are quite verbose)
   *
   * @var bool
   */
  protected $_dynamicAction = FALSE;

  /**
   * Class constructor.
   *
   * @param CRM_Core_Selector_API $object
   *   An object that implements the selector API.
   * @param int $pageID
   *   Default pageID.
   * @param int $sortID
   *   Default sortID.
   * @param int $action
   *   The actions to potentially support.
   * @param CRM_Core_Page|CRM_Core_Form $store place in session to store some values
   * @param int $output
   *   What do we so with the output, session/template//both.
   *
   * @param null $prefix
   * @param null $case
   *
   * @return \CRM_Core_Selector_Controller
   */
  public function __construct($object, $pageID, $sortID, $action, $store = NULL, $output = self::TEMPLATE, $prefix = NULL, $case = NULL) {

    $this->_object = $object;
    $this->_pageID = $pageID ? $pageID : 1;
    $this->_sortID = $sortID ? $sortID : NULL;
    $this->_action = $action;
    $this->_store = $store;
    $this->_output = $output;
    $this->_prefix = $prefix;
    $this->_case = $case;

    // fix sortID
    if ($this->_sortID && strpos($this->_sortID, '_') === FALSE) {
      $this->_sortID .= '_u';
    }

    $params = [
      'pageID' => $this->_pageID,
    ];

    // let the constructor initialize this, should happen only once
    if (!isset(self::$_template)) {
      self::$_template = CRM_Core_Smarty::singleton();
    }

    $this->_sortOrder = &$this->_object->getSortOrder($action);
    $this->_sort = new CRM_Utils_Sort($this->_sortOrder, $this->_sortID);

    /*
     * if we are in transfer mode, do not goto database, use the
     * session values instead
     */

    if ($output == self::TRANSFER) {
      $params['total'] = $this->_store->get($this->_prefix . 'rowCount');
    }
    else {
      $params['total'] = $this->_object->getTotalCount($action, $this->_case);
    }

    $this->_total = $params['total'];
    $this->_object->getPagerParams($action, $params);

    /*
     * Set the default values of RowsPerPage
     */

    $storeRowCount = $store->get($this->_prefix . CRM_Utils_Pager::PAGE_ROWCOUNT);
    if ($storeRowCount) {
      $params['rowCount'] = $storeRowCount;
    }
    elseif (!isset($params['rowCount'])) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $this->_pager = new CRM_Utils_Pager($params);
    list($this->_pagerOffset, $this->_pagerRowCount) = $this->_pager->getOffsetAndRowCount();
  }

  /**
   * Have the GET vars changed, i.e. pageId or sortId that forces us to recompute the search values
   *
   * @param int $reset
   *   Are we being reset.
   *
   * @return bool
   *   if the GET params are different from the session params
   */
  public function hasChanged($reset) {

    /**
     * if we are in reset state, i.e the store is cleaned out, we return false
     * we also return if we dont have a record of the sort id or page id
     */
    if ($reset ||
      $this->_store->get($this->_prefix . CRM_Utils_Pager::PAGE_ID) == NULL ||
      $this->_store->get($this->_prefix . CRM_Utils_Sort::SORT_ID) == NULL
    ) {
      return FALSE;
    }

    if ($this->_store->get($this->_prefix . CRM_Utils_Pager::PAGE_ID) != $this->_pager->getCurrentPageID() ||
      $this->_store->get($this->_prefix . CRM_Utils_Sort::SORT_ID) != $this->_sort->getCurrentSortID() ||
      $this->_store->get($this->_prefix . CRM_Utils_Sort::SORT_DIRECTION) != $this->_sort->getCurrentSortDirection()
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Heart of the Controller. This is where all the action takes place
   *
   *   - The rows are fetched and stored depending on the type of output needed
   *
   *   - For export/printing all rows are selected.
   *
   *   - for displaying on screen paging parameters are used to display the
   *     required rows.
   *
   *   - also depending on output type of session or template rows are appropriately stored in session
   *     or template variables are updated.
   *
   *
   * @return void
   */
  public function run() {

    // get the column headers
    $columnHeaders = &$this->_object->getColumnHeaders($this->_action, $this->_output);

    $contextArray = explode('_', get_class($this->_object));

    $contextName = strtolower($contextArray[1]);

    // fix contribute and member
    if ($contextName == 'contribute') {
      $contextName = 'contribution';
    }
    elseif ($contextName == 'member') {
      $contextName = 'membership';
    }

    // we need to get the rows if we are exporting or printing them
    if ($this->_output == self::EXPORT || $this->_output == self::SCREEN) {
      // get rows (without paging criteria)
      $rows = self::getRows($this);
      CRM_Utils_Hook::searchColumns($contextName, $columnHeaders, $rows, $this);
      if ($this->_output == self::EXPORT) {
        // export the rows.
        CRM_Core_Report_Excel::writeCSVFile($this->_object->getExportFileName(),
          $columnHeaders,
          $rows
        );
        CRM_Utils_System::civiExit();
      }
      else {
        // assign to template and display them.
        self::$_template->assign_by_ref('rows', $rows);
        self::$_template->assign_by_ref('columnHeaders', $columnHeaders);
      }
    }
    else {
      // output requires paging/sorting capability
      $rows = self::getRows($this);
      CRM_Utils_Hook::searchColumns($contextName, $columnHeaders, $rows, $this);
      $reorderedHeaders = [];
      $noWeightHeaders = [];
      foreach ($columnHeaders as $key => $columnHeader) {
        // So far only contribution selector sets weight, so just use key if not.
        // Extension writers will need to fix other getColumnHeaders (or add a wrapper)
        // to extend.
        if (isset($columnHeader['weight'])) {
          $reorderedHeaders[$columnHeader['weight']] = $columnHeader;
        }
        else {
          $noWeightHeaders[$key] = $columnHeader;
        }
      }
      ksort($reorderedHeaders);
      // Merge headers not containing weight to ordered headers
      $finalColumnHeaders = array_merge($reorderedHeaders, $noWeightHeaders);

      $rowsEmpty = count($rows) ? FALSE : TRUE;
      $qill = $this->getQill();
      $summary = $this->getSummary();
      // if we need to store in session, lets update session
      if ($this->_output & self::SESSION) {
        $this->_store->set("{$this->_prefix}columnHeaders", $finalColumnHeaders);
        if ($this->_dynamicAction) {
          $this->_object->removeActions($rows);
        }
        $this->_store->set("{$this->_prefix}rows", $rows);
        $this->_store->set("{$this->_prefix}rowCount", $this->_total);
        $this->_store->set("{$this->_prefix}rowsEmpty", $rowsEmpty);
        $this->_store->set("{$this->_prefix}qill", $qill);
        $this->_store->set("{$this->_prefix}summary", $summary);
      }
      else {
        self::$_template->assign_by_ref("{$this->_prefix}pager", $this->_pager);
        self::$_template->assign_by_ref("{$this->_prefix}sort", $this->_sort);

        self::$_template->assign_by_ref("{$this->_prefix}columnHeaders", $finalColumnHeaders);
        self::$_template->assign_by_ref("{$this->_prefix}rows", $rows);
        self::$_template->assign("{$this->_prefix}rowsEmpty", $rowsEmpty);
        self::$_template->assign("{$this->_prefix}qill", $qill);
        self::$_template->assign("{$this->_prefix}summary", $summary);
      }

      // always store the current pageID and sortID
      $this->_store->set($this->_prefix . CRM_Utils_Pager::PAGE_ID,
        $this->_pager->getCurrentPageID()
      );
      $this->_store->set($this->_prefix . CRM_Utils_Sort::SORT_ID,
        $this->_sort->getCurrentSortID()
      );
      $this->_store->set($this->_prefix . CRM_Utils_Sort::SORT_DIRECTION,
        $this->_sort->getCurrentSortDirection()
      );
      $this->_store->set($this->_prefix . CRM_Utils_Sort::SORT_ORDER,
        $this->_sort->orderBy()
      );
      $this->_store->set($this->_prefix . CRM_Utils_Pager::PAGE_ROWCOUNT,
        $this->_pager->_perPage
      );
    }
  }

  /**
   * Retrieve rows.
   *
   * @param CRM_Core_Form $form
   *
   * @return array
   *   Array of rows
   */
  public function getRows($form) {
    if ($form->_output == self::EXPORT || $form->_output == self::SCREEN) {
      //get rows (without paging criteria)
      return $form->_object->getRows($form->_action, 0, 0, $form->_sort, $form->_output);
    }
    else {
      return $form->_object->getRows($form->_action, $form->_pagerOffset, $form->_pagerRowCount,
        $form->_sort, $form->_output, $form->_case
      );
    }
  }

  /**
   * Default function for qill, if needed to be implemented, we
   * expect the subclass to do it
   *
   * @return string
   *   the status message
   */
  public function getQill() {
    return $this->_object->getQill();
  }

  /**
   * @return mixed
   */
  public function getSummary() {
    return $this->_object->getSummary();
  }

  /**
   * Getter for pager.
   *
   * @return CRM_Utils_Pager
   */
  public function getPager() {
    return $this->_pager;
  }

  /**
   * Getter for sort.
   *
   * @return CRM_Utils_Sort
   */
  public function getSort() {
    return $this->_sort;
  }

  /**
   * Move the variables from the session to the template.
   *
   * @return void
   */
  public function moveFromSessionToTemplate() {
    self::$_template->assign_by_ref("{$this->_prefix}pager", $this->_pager);

    $rows = $this->_store->get("{$this->_prefix}rows");

    if ($rows) {
      if ($this->_dynamicAction) {
        $this->_object->addActions($rows);
      }

      self::$_template->assign("{$this->_prefix}aToZ",
        $this->_store->get("{$this->_prefix}AToZBar")
      );
    }

    self::$_template->assign_by_ref("{$this->_prefix}sort", $this->_sort);
    self::$_template->assign("{$this->_prefix}columnHeaders", $this->_store->get("{$this->_prefix}columnHeaders"));
    self::$_template->assign("{$this->_prefix}rows", $rows);
    self::$_template->assign("{$this->_prefix}rowsEmpty", $this->_store->get("{$this->_prefix}rowsEmpty"));
    self::$_template->assign("{$this->_prefix}qill", $this->_store->get("{$this->_prefix}qill"));
    self::$_template->assign("{$this->_prefix}summary", $this->_store->get("{$this->_prefix}summary"));

    if ($this->_embedded) {
      return;
    }

    self::$_template->assign('tplFile', $this->_object->getHookedTemplateFileName());
    if ($this->_print) {
      $content = self::$_template->fetch('CRM/common/print.tpl');
    }
    else {
      $config = CRM_Core_Config::singleton();
      $content = self::$_template->fetch('CRM/common/' . strtolower($config->userFramework) . '.tpl');
    }
    echo CRM_Utils_System::theme($content, $this->_print);
  }

  /**
   * Setter for embedded.
   *
   * @param bool $embedded
   *
   * @return void
   */
  public function setEmbedded($embedded) {
    $this->_embedded = $embedded;
  }

  /**
   * Getter for embedded.
   *
   * @return bool
   *   return the embedded value
   */
  public function getEmbedded() {
    return $this->_embedded;
  }

  /**
   * Setter for print.
   *
   * @param bool $print
   *
   * @return void
   */
  public function setPrint($print) {
    $this->_print = $print;
  }

  /**
   * Getter for print.
   *
   * @return bool
   *   return the print value
   */
  public function getPrint() {
    return $this->_print;
  }

  /**
   * @param $value
   */
  public function setDynamicAction($value) {
    $this->_dynamicAction = $value;
  }

  /**
   * @return bool
   */
  public function getDynamicAction() {
    return $this->_dynamicAction;
  }

}
