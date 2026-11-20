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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Utils_Pager
 */
class CRM_Utils_Pager {

  /**
   * Constants for static parameters of the pager
   */
  const PAGE_ID = 'crmPID', PAGE_ID_TOP = 'crmPID', PAGE_ID_BOTTOM = 'crmPID_B', PAGE_ROWCOUNT = 'crmRowCount';

  /**
   * Deprecated constants that might still be used by extensions but no longer by core
   * @deprecated
   */
  const ROWCOUNT = 50;

  /**
   * Total number of items across all pages.
   * @var int
   */
  public $_totalItems = 0;

  /**
   * Number of items per page.
   * @var int
   */
  public $_perPage = 25;

  /**
   * Current page ID (1-indexed).
   * @var int
   */
  public $_currentPage = 1;

  /**
   * Total number of pages.
   * @var int
   */
  public $_totalPages = 1;

  /**
   * The output of the pager. This is a name/value array with various keys
   * that an application could use to display the pager
   *
   * @var array
   */
  public $_response;

  private $_urlVar = self::PAGE_ID;
  private $_altFirst = 'first page';
  private $_altPrev = 'previous page';
  private $_altNext = 'next page';
  private $_altLast = 'last page';
  private $_prevImg = ' &lt; Previous';
  private $_nextImg = 'Next &gt; ';
  private $_firstPagePre = '';
  private $_firstPageText = ' &lt;&lt; First';
  private $_firstPagePost = '';
  private $_lastPagePre = '';
  private $_lastPageText = 'Last &gt;&gt; ';
  private $_lastPagePost = '';
  private $_spacesBefore = '&nbsp;';
  private $_spacesAfter = '&nbsp;';
  private $_excludeVars = [];
  private $_extraVars = [];
  private $_linkData = NULL;

  /**
   * The pager constructor.
   *
   * @param array $params
   *   Array of parameters for the pager, including:
   *   - 'total': int, total number of records across all pages.
   *   - 'status': string|null, template status message string containing '%%StatusMessage%%'.
   *   - 'rowCount': int|null, number of records per page.
   *   - 'pageID': int|null, current page number.
   *   - 'buttonTop': string|null, name of the form element for the top submit button.
   *   - 'buttonBottom': string|null, name of the form element for the bottom submit button.
   *   - 'csvString': string|null, optional CSV download link or string.
   *
   * @return \CRM_Utils_Pager the newly created and initialized pager object
   */
  public function __construct($params) {
    $params['status'] ??= ts('Contacts %%StatusMessage%%');

    $this->initialize($params);

    $this->_totalItems = (int) ($params['totalItems'] ?? $params['total'] ?? 0);
    $this->_perPage = (int) ($params['perPage'] ?? 25);
    $this->_urlVar = $params['urlVar'] ?? self::PAGE_ID;
    $this->_altFirst = $params['altFirst'] ?? 'first page';
    $this->_altPrev = $params['altPrev'] ?? 'previous page';
    $this->_altNext = $params['altNext'] ?? 'next page';
    $this->_altLast = $params['altLast'] ?? 'last page';
    $this->_prevImg = $params['prevImg'] ?? ' ' . ts('&lt; Previous');
    $this->_nextImg = $params['nextImg'] ?? ts('Next &gt;') . ' ';
    $this->_firstPagePre = $params['firstPagePre'] ?? '';
    $this->_firstPageText = $params['firstPageText'] ?? ' ' . ts('&lt;&lt; First');
    $this->_firstPagePost = $params['firstPagePost'] ?? '';
    $this->_lastPagePre = $params['lastPagePre'] ?? '';
    $this->_lastPageText = $params['lastPageText'] ?? ts('Last &gt;&gt;') . ' ';
    $this->_lastPagePost = $params['lastPagePost'] ?? '';
    $this->_spacesBefore = str_repeat('&nbsp;', $params['spacesBeforeSeparator'] ?? 1);
    $this->_spacesAfter = str_repeat('&nbsp;', $params['spacesAfterSeparator'] ?? 1);
    $this->_excludeVars = $params['excludeVars'] ?? [];
    $this->_extraVars = $params['extraVars'] ?? [];

    $this->_totalPages = $this->_perPage > 0 ? (int) ceil($this->_totalItems / $this->_perPage) : 0;

    $currentPage = (int) ($params['currentPage'] ?? 1);
    if ($currentPage < 1) {
      $currentPage = 1;
    }
    if ($this->_totalPages > 0 && $currentPage > $this->_totalPages) {
      $currentPage = $this->_totalPages;
    }
    $this->_currentPage = $currentPage;

    [$offset, $limit] = $this->getOffsetAndRowCount();
    $start = $offset + 1;
    $end = $offset + $limit;
    if ($end > $this->_totalItems) {
      $end = $this->_totalItems;
    }

    if ($this->_totalItems == 0) {
      $statusMessage = '';
    }
    else {
      $statusMessage = ts('%1 - %2 of %3', [1 => $start, 2 => $end, 3 => $this->_totalItems]);
    }
    $params['status'] = str_replace('%%StatusMessage%%', $statusMessage, $params['status']);

    $this->_response = [
      'first' => $this->getFirstPageLink(),
      'back' => $this->getBackPageLink(),
      'next' => $this->getNextPageLink(),
      'last' => $this->getLastPageLink(),
      'currentPage' => $this->getCurrentPageID(),
      'numPages' => $this->numPages(),
      'csvString' => $params['csvString'] ?? NULL,
      'status' => $params['status'] ?? NULL,
      'buttonTop' => $params['buttonTop'] ?? NULL,
      'buttonBottom' => $params['buttonBottom'] ?? NULL,
      'currentLocation' => $this->getCurrentLocation(),
    ];

    /**
     * A page cannot have two variables with the same form name. Hence in the
     * pager display, we have a form submission at the top with the normal
     * page variable, but a different form element for one at the bottom.
     */
    $this->_response['titleTop'] = ts('Page %1 of %2', [
      1 => '<input size="2" maxlength="4" name="' . self::PAGE_ID . '" type="text" value="' . $this->_response['currentPage'] . '" />',
      2 => $this->_response['numPages'],
    ]);
    $this->_response['titleBottom'] = ts('Page %1 of %2', [
      1 => '<input size="2" maxlength="4" name="' . self::PAGE_ID_BOTTOM . '" type="text" value="' . $this->_response['currentPage'] . '" />',
      2 => $this->_response['numPages'],
    ]);
  }

  /**
   * Helper function to assign remaining pager options as good default
   * values.
   *
   * @param array $params
   *
   * @return array
   */
  public function initialize(&$params) {
    $params['mode'] = 'Sliding';
    $params['urlVar'] = self::PAGE_ID;
    $params['delta'] = 1;
    $params['totalItems'] = $params['total'] ?? 0;
    $params['append'] = TRUE;
    $params['separator'] = '';
    $params['spacesBeforeSeparator'] = 1;
    $params['spacesAfterSeparator'] = 1;
    $params['extraVars'] = ['force' => 1];
    $params['excludeVars'] = ['reset', 'snippet', 'section'];

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

    $defaultPageId = $params['pageID'] ?? 1;
    $params['currentPage'] = $this->getPageID($defaultPageId, $params);

    $params['perPage'] = $this->getPageRowCount($params['rowCount'] ?? NULL);

    return $params;
  }

  /**
   * Figure out the current page number based on value of GET / POST variables.
   *
   * @param int $defaultPageId
   * @param array $params
   *
   * @return int
   */
  public function getPageID($defaultPageId, &$params) {
    $currentPage = $defaultPageId;
    if (!empty($_POST)) {
      if (isset($params['buttonTop'], $_POST[$params['buttonTop']], $_POST[self::PAGE_ID])) {
        $currentPage = max((int) @$_POST[self::PAGE_ID], 1);
      }
      elseif (isset($params['buttonBottom'], $_POST[$params['buttonBottom']], $_POST[self::PAGE_ID_BOTTOM])) {
        $currentPage = max((int) @$_POST[self::PAGE_ID_BOTTOM], 1);
      }
      elseif (isset($_POST[self::PAGE_ID])) {
        $currentPage = max((int) @$_POST[self::PAGE_ID], 1);
      }
      elseif (isset($_POST[self::PAGE_ID_BOTTOM])) {
        $currentPage = max((int) @$_POST[self::PAGE_ID_BOTTOM], 1);
      }
    }
    elseif (isset($_GET[self::PAGE_ID])) {
      $currentPage = max((int) @$_GET[self::PAGE_ID], 1);
    }
    return $currentPage;
  }

  /**
   * Get the number of rows to display from either a GET / POST variable
   *
   * @param int|null $defaultPageRowCount
   *
   * @return int
   */
  public function getPageRowCount($defaultPageRowCount = NULL) {
    if (isset($_POST[self::PAGE_ROWCOUNT])) {
      $rowCount = max((int) @$_POST[self::PAGE_ROWCOUNT], 1);
    }
    elseif (isset($_GET[self::PAGE_ROWCOUNT])) {
      $rowCount = max((int) @$_GET[self::PAGE_ROWCOUNT], 1);
    }
    else {
      if (empty($defaultPageRowCount)) {
        $rowCount = Civi::settings()->get('default_pager_size');
      }
      else {
        $rowCount = $defaultPageRowCount;
      }
    }
    return $rowCount;
  }

  /**
   * Return page ID and offset.
   *
   * @return array
   */
  public function getOffsetAndRowCount() {
    $pageId = $this->getCurrentPageID();
    if (!$pageId) {
      $pageId = 1;
    }

    $offset = ($pageId - 1) * $this->_perPage;

    return [$offset, $this->_perPage];
  }

  /**
   * @return int
   */
  public function getCurrentPageID(): int {
    return $this->_currentPage;
  }

  /**
   * @return int
   */
  public function numPages(): int {
    return $this->_totalPages;
  }

  /**
   * @return int
   */
  public function numItems(): int {
    return $this->_totalItems;
  }

  /**
   * @return bool
   */
  public function isFirstPage(): bool {
    return $this->_currentPage < 2;
  }

  /**
   * @return bool
   */
  public function isLastPage(): bool {
    return $this->_currentPage == $this->_totalPages || $this->_totalPages == 0;
  }

  /**
   * @return int|false
   */
  public function getNextPageID() {
    return ($this->getCurrentPageID() == $this->numPages() || $this->numPages() == 0) ? FALSE : $this->getCurrentPageID() + 1;
  }

  /**
   * @return int|false
   */
  public function getPreviousPageID() {
    return $this->isFirstPage() ? FALSE : $this->getCurrentPageID() - 1;
  }

  /**
   * @return string
   */
  public function getCurrentLocation() {
    $path = CRM_Utils_System::currentPath();
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

  /**
   * Return any extra GET/POST args that should be appended to links.
   *
   * @return array
   * @access private
   */
  private function _getLinksData() {
    // This all feels quirky, but it's loyal to the prior implementation.
    // It's just rewritten/flattened/simplified.

    $qs = (!isset($_GET[$this->_urlVar]) && isset($_POST[$this->_urlVar])) ? $_POST : $_GET;

    foreach (array_intersect($this->_excludeVars, array_keys($qs)) as $key) {
      unset($qs[$key]);
    }

    $decode = fn($val) => strtr($val, array_flip(get_html_translation_table(HTML_ENTITIES)));
    $map = fn($func, $data) => is_array($data) ? array_map($func, $data) : $func($data);
    foreach ($this->_extraVars ?? [] as $var => $value) {
      $qs[$var] = $map($decode, $this->_extraVars[$var]);
    }

    return $qs;
  }

  public function __get(string $name) {
    if ($name === '_linkData') {
      // We don't usually need this, but some extensions (eg `countrymanager`) may use it.
      $this->_linkData ??= $this->_getLinksData();
      return $this->_linkData;
    }

    // Note: If you get here, it may be that you relied on an obscure from the old PEAR Pager class.
    // We no longer extend this.
    $trace = debug_backtrace();
    trigger_error('Undefined property via CRM_Utils_Pager::__get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'], E_USER_WARNING);
    return NULL;
  }

  public function __isset(string $name) {
    return in_array($name, ['_linkData'], TRUE);
  }

}
