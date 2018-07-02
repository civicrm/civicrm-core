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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class extends the PEAR pager object by substituting standard default pager arguments
 * We also extract the pageId from either the GET variables or the POST variable (since we
 * use a POST to jump to a specific page). At some point we should evaluate if we want
 * to use Pager_Jumping instead. We've changed the format to allow navigation by jumping
 * to a page and also First, Prev CURRENT Next Last
 */

require_once 'Pager/Sliding.php';

/**
 * Class CRM_Utils_Pager
 */
class CRM_Utils_Pager extends Pager_Sliding {

  /**
   * Constants for static parameters of the pager
   */
  const ROWCOUNT = 50, PAGE_ID = 'crmPID', PAGE_ID_TOP = 'crmPID', PAGE_ID_BOTTOM = 'crmPID_B', PAGE_ROWCOUNT = 'crmRowCount';

  /**
   * The output of the pager. This is a name/value array with various keys
   * that an application could use to display the pager
   *
   * @var array
   */
  public $_response;

  /**
   * The pager constructor. Takes a few values, and then assigns a lot of defaults
   * to the PEAR pager class
   * We have embedded some html in this class. Need to figure out how to export this
   * to the top level at some point in time
   *
   * @param array $params
   *
   * @return \CRM_Utils_Pager the newly created and initialized pager object
   */
  public function __construct($params) {
    if ($params['status'] === NULL) {
      $params['status'] = ts('Contacts %%StatusMessage%%');
    }

    $this->initialize($params);

    parent::__construct($params);

    list($offset, $limit) = $this->getOffsetAndRowCount();
    $start = $offset + 1;
    $end = $offset + $limit;
    if ($end > $params['total']) {
      $end = $params['total'];
    }

    if ($params['total'] == 0) {
      $statusMessage = '';
    }
    else {
      $statusMessage = ts('%1 - %2 of %3', array(1 => $start, 2 => $end, 3 => $params['total']));
    }
    $params['status'] = str_replace('%%StatusMessage%%', $statusMessage, $params['status']);

    $this->_response = array(
      'first' => $this->getFirstPageLink(),
      'back' => $this->getBackPageLink(),
      'next' => $this->getNextPageLink(),
      'last' => $this->getLastPageLink(),
      'currentPage' => $this->getCurrentPageID(),
      'numPages' => $this->numPages(),
      'csvString' => CRM_Utils_Array::value('csvString', $params),
      'status' => CRM_Utils_Array::value('status', $params),
      'buttonTop' => CRM_Utils_Array::value('buttonTop', $params),
      'buttonBottom' => CRM_Utils_Array::value('buttonBottom', $params),
      'currentLocation' => $this->getCurrentLocation(),
    );

    /**
     * A page cannot have two variables with the same form name. Hence in the
     * pager display, we have a form submission at the top with the normal
     * page variable, but a different form element for one at the bottom.
     */
    $this->_response['titleTop'] = ts('Page %1 of %2', array(
        1 => '<input size="2" maxlength="4" name="' . self::PAGE_ID . '" type="text" value="' . $this->_response['currentPage'] . '" />',
        2 => $this->_response['numPages'],
      ));
    $this->_response['titleBottom'] = ts('Page %1 of %2', array(
        1 => '<input size="2" maxlength="4" name="' . self::PAGE_ID_BOTTOM . '" type="text" value="' . $this->_response['currentPage'] . '" />',
        2 => $this->_response['numPages'],
      ));
  }

  /**
   * Helper function to assign remaining pager options as good default
   * values.
   *
   * @param array $params
   *   The set of options needed to initialize the parent constructor.
   *
   * @return array
   */
  public function initialize(&$params) {
    // set the mode for the pager to Sliding

    $params['mode'] = 'Sliding';

    // also set the urlVar to be a crm specific get variable.

    $params['urlVar'] = self::PAGE_ID;

    // set this to a small value, since we dont use this functionality

    $params['delta'] = 1;

    $params['totalItems'] = $params['total'];
    $params['append'] = TRUE;
    $params['separator'] = '';
    $params['spacesBeforeSeparator'] = 1;
    $params['spacesAfterSeparator'] = 1;
    $params['extraVars'] = array('force' => 1);
    $params['excludeVars'] = array('reset', 'snippet', 'section');

    // set previous and next text labels
    $params['prevImg'] = ' ' . ts('&lt; Previous');
    $params['nextImg'] = ts('Next &gt;') . ' ';

    // set first and last text fragments
    $params['firstPagePre'] = '';
    $params['firstPageText'] = ' ' . ts('&lt;&lt; First');
    $params['firstPagePost'] = '';

    $params['lastPagePre'] = '';
    $params['lastPageText'] = ts('Last &gt;&gt;') . ' ';
    $params['lastPagePost'] = '';

    if (isset($params['pageID'])) {
      $params['currentPage'] = $this->getPageID($params['pageID'], $params);
    }

    $params['perPage'] = $this->getPageRowCount($params['rowCount']);

    return $params;
  }

  /**
   * Figure out the current page number based on value of
   * GET / POST variables. Hierarchy rules are followed,
   * POST over-rides a GET, a POST at the top overrides
   * a POST at the bottom (of the page)
   *
   * @param int $defaultPageId
   *   DefaultPageId current pageId.
   *
   * @param array $params
   *
   * @return int
   *   new pageId to display to the user
   */
  public function getPageID($defaultPageId = 1, &$params) {
    // POST has higher priority than GET vars
    // else if a value is set that has higher priority and finally the GET var
    $currentPage = $defaultPageId;
    if (!empty($_POST)) {
      if (isset($_POST[CRM_Utils_Array::value('buttonTop', $params)]) && isset($_POST[self::PAGE_ID])) {
        $currentPage = max((int ) @$_POST[self::PAGE_ID], 1);
      }
      elseif (isset($_POST[$params['buttonBottom']]) && isset($_POST[self::PAGE_ID_BOTTOM])) {
        $currentPage = max((int ) @$_POST[self::PAGE_ID_BOTTOM], 1);
      }
      elseif (isset($_POST[self::PAGE_ID])) {
        $currentPage = max((int ) @$_POST[self::PAGE_ID], 1);
      }
      elseif (isset($_POST[self::PAGE_ID_BOTTOM])) {
        $currentPage = max((int ) @$_POST[self::PAGE_ID_BOTTOM]);
      }
    }
    elseif (isset($_GET[self::PAGE_ID])) {
      $currentPage = max((int ) @$_GET[self::PAGE_ID], 1);
    }
    return $currentPage;
  }

  /**
   * Get the number of rows to display from either a GET / POST variable
   *
   * @param int $defaultPageRowCount
   *   The default value if not set.
   *
   * @return int
   *   the rowCount value to use
   */
  public function getPageRowCount($defaultPageRowCount = self::ROWCOUNT) {
    // POST has higher priority than GET vars
    if (isset($_POST[self::PAGE_ROWCOUNT])) {
      $rowCount = max((int ) @$_POST[self::PAGE_ROWCOUNT], 1);
    }
    elseif (isset($_GET[self::PAGE_ROWCOUNT])) {
      $rowCount = max((int ) @$_GET[self::PAGE_ROWCOUNT], 1);
    }
    else {
      $rowCount = $defaultPageRowCount;
    }
    return $rowCount;
  }

  /**
   * Use the pager class to get the pageId and Offset.
   *
   * @return array
   *   an array of the pageID and offset
   */
  public function getOffsetAndRowCount() {
    $pageId = $this->getCurrentPageID();
    if (!$pageId) {
      $pageId = 1;
    }

    $offset = ($pageId - 1) * $this->_perPage;

    return array($offset, $this->_perPage);
  }

  /**
   * @return string
   */
  public function getCurrentLocation() {
    $config = CRM_Core_Config::singleton();
    $path = CRM_Utils_Array::value($config->userFrameworkURLVar, $_GET);
    return CRM_Utils_System::url($path, CRM_Utils_System::getLinksUrl(self::PAGE_ID, FALSE, TRUE), FALSE, NULL, FALSE) . $this->getCurrentPageID();
  }

  /**
   * @return string
   */
  public function getFirstPageLink() {
    if ($this->isFirstPage()) {
      return '';
    }
    $href = $this->makeURL(self::PAGE_ID, 1);
    return $this->formatLink($href, str_replace('%d', 1, $this->_altFirst), $this->_firstPagePre . $this->_firstPageText . $this->_firstPagePost) .
    $this->_spacesBefore . $this->_spacesAfter;
  }

  /**
   * @return string
   */
  public function getLastPageLink() {
    if ($this->isLastPage()) {
      return '';
    }
    $href = $this->makeURL(self::PAGE_ID, $this->_totalPages);
    return $this->formatLink($href, str_replace('%d', $this->_totalPages, $this->_altLast), $this->_lastPagePre . $this->_lastPageText . $this->_lastPagePost);
  }

  /**
   * @return string
   */
  public function getBackPageLink() {
    if ($this->_currentPage > 1) {
      $href = $this->makeURL(self::PAGE_ID, $this->getPreviousPageID());
      return $this->formatLink($href, $this->_altPrev, $this->_prevImg) . $this->_spacesBefore . $this->_spacesAfter;
    }
    return '';
  }

  /**
   * @return string
   */
  public function getNextPageLink() {
    if ($this->_currentPage < $this->_totalPages) {
      $href = $this->makeURL(self::PAGE_ID, $this->getNextPageID());
      return $this->_spacesAfter .
      $this->formatLink($href, $this->_altNext, $this->_nextImg) .
      $this->_spacesBefore . $this->_spacesAfter;
    }
    return '';
  }

  /**
   * Build a url for pager links.
   *
   * @param string $key
   * @param string $value
   *
   * @return string
   */
  public function makeURL($key, $value) {
    $href = CRM_Utils_System::makeURL($key, TRUE);
    // CRM-12212 Remove alpha sort param
    if (strpos($href, '&amp;sortByCharacter=')) {
      $href = preg_replace('#(.*)\&amp;sortByCharacter=[^&]*(.*)#', '\1\2', $href);
    }
    return $href . $value;
  }

  /**
   * Output the html pager link.
   * @param string $href
   * @param string $title
   * @param string $image
   * @return string
   */
  private function formatLink($href, $title, $image) {
    return sprintf('<a class="crm-pager-link action-item crm-hover-button" href="%s" title="%s">%s</a>', $href, $title, $image);
  }

}
