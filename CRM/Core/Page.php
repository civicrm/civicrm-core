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
 * A Page is basically data in a nice pretty format.
 *
 * Pages should not have any form actions / elements in them. If they
 * do, make sure you use CRM_Core_Form and the related structures. You can
 * embed simple forms in Page and do your own form handling.
 *
 */
class CRM_Core_Page {

  /**
   * The name of the page (auto generated from class name)
   *
   * @var string
   */
  protected $_name;

  /**
   * The title associated with this page.
   *
   * @var object
   */
  protected $_title;

  /**
   * A page can have multiple modes. (i.e. displays
   * a different set of data based on the input
   * @var int
   */
  protected $_mode;

  /**
   * Is this object being embedded in another object. If
   * so the display routine needs to not do any work. (The
   * parent object takes care of the display)
   *
   * @var boolean
   */
  protected $_embedded = FALSE;

  /**
   * Are we in print mode? if so we need to modify the display
   * functionality to do a minimal display :)
   *
   * @var boolean
   */
  protected $_print = FALSE;

  /**
   * Do we need to reset the controller session for this request?
   *
   * @var boolean
   */
  protected $_reset = FALSE;

  /**
   * Cache the smarty template for efficiency reasons
   *
   * @var CRM_Core_Smarty
   */
  static protected $_template;

  /**
   * Cache the session for efficiency reasons
   *
   * @var CRM_Core_Session
   */
  static protected $_session;

  /**
   * What to return to the client if in ajax mode (snippet=json)
   *
   * @var array
   */
  public $ajaxResponse = array();

  /**
   * Url path used to reach this page
   *
   * @var array
   */
  public $urlPath = array();

  /**
   * Should crm.livePage.js be added to the page?
   * @var bool
   */
  public $useLivePageJS;

  /**
   * Class constructor.
   *
   * @param string $title
   *   Title of the page.
   * @param int $mode
   *   Mode of the page.
   *
   * @return CRM_Core_Page
   */
  public function __construct($title = NULL, $mode = NULL) {
    $this->_name = CRM_Utils_System::getClassName($this);
    $this->_title = $title;
    $this->_mode = $mode;

    // let the constructor initialize this, should happen only once
    if (!isset(self::$_template)) {
      self::$_template = CRM_Core_Smarty::singleton();
      self::$_session = CRM_Core_Session::singleton();
    }

  }

  /**
   * This function takes care of all the things common to all pages.
   *
   * This typically involves assigning the appropriate smarty variables :)
   */
  public function run() {
    if ($this->_reset) {
      $this->reset();
    }

    if ($this->_embedded) {
      return NULL;
    }

    self::$_template->assign('mode', $this->_mode);

    $pageTemplateFile = $this->getHookedTemplateFileName();
    self::$_template->assign('tplFile', $pageTemplateFile);

    // invoke the pagRun hook, CRM-3906
    CRM_Utils_Hook::pageRun($this);

    if ($this->_print) {
      if (in_array($this->_print, array(
        CRM_Core_Smarty::PRINT_SNIPPET,
        CRM_Core_Smarty::PRINT_NOFORM,
        CRM_Core_Smarty::PRINT_JSON,
        CRM_Core_Smarty::PRINT_QFKEY,
        CRM_Core_Smarty::PRINT_PAGE
      ))) {
        // pull in the page's contents.
        $content = self::$_template->fetch('CRM/common/snippet.tpl');
      }
      else {
        // Items that pull in this template trigger the client print dialog.
        $content = self::$_template->fetch('CRM/common/print.tpl');
      }

      CRM_Utils_System::appendTPLFile($pageTemplateFile,
        $content,
        $this->overrideExtraTemplateFileName()
      );

      // It's time to call the hook.
      CRM_Utils_Hook::alterContent($content, 'page', $pageTemplateFile, $this);

      if ($this->_print == CRM_Core_Smarty::PRINT_PDF) {
        CRM_Utils_PDF_Utils::html2pdf($content, "{$this->_name}.pdf", FALSE,
          array('paper_size' => 'a3', 'orientation' => 'landscape')
        );
      }
      elseif ($this->_print == CRM_Core_Smarty::PRINT_JSON) {
        $this->ajaxResponse['content'] = $content;
        CRM_Core_Page_AJAX::returnJsonResponse($this->ajaxResponse);
      }
      elseif ($this->_print == CRM_Core_Smarty::PRINT_SNIPPET ||
              $this->_print == CRM_Core_Smarty::PRINT_QFKEY ||
              $this->_print == CRM_Core_Smarty::PRINT_PAGE
      ) {
        return $content;
      }
      else
      {
	 CRM_Utils_System::civiExit();
      }
    }

    $config = CRM_Core_Config::singleton();

    // Show intermittent alerts to admins
    CRM_Utils_Check::singleton()->showPeriodicAlerts();

    if ($this->useLivePageJS && Civi::settings()->get('ajaxPopupsEnabled')) {
      CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'js/crm.livePage.js', 1, 'html-header');
    }

    $content = self::$_template->fetch('CRM/common/' . strtolower($config->userFramework) . '.tpl');

    // Render page header
    if (!defined('CIVICRM_UF_HEAD') && $region = CRM_Core_Region::instance('html-header', FALSE)) {
      CRM_Utils_System::addHTMLHead($region->render(''));
    }
    CRM_Utils_System::appendTPLFile($pageTemplateFile, $content);

    //its time to call the hook.
    CRM_Utils_Hook::alterContent($content, 'page', $pageTemplateFile, $this);
    CRM_Utils_System::theme($content, $this->_print);

    return $content;
}

  /**
   * Store the variable with the value in the form scope.
   *
   * @param string|array $name name of the variable or an assoc array of name/value pairs
   * @param mixed $value
   *   Value of the variable if string.
   */
  public function set($name, $value = NULL) {
    self::$_session->set($name, $value, $this->_name);
  }

  /**
   * Get the variable from the form scope.
   *
   * @param string $name name of the variable
   *
   * @return mixed
   */
  public function get($name) {
    return self::$_session->get($name, $this->_name);
  }

  /**
   * Assign value to name in template.
   *
   * @param string $var
   * @param mixed $value
   *   Value of variable.
   */
  public function assign($var, $value = NULL) {
    self::$_template->assign($var, $value);
  }

  /**
   * Assign value to name in template by reference.
   *
   * @param string $var
   * @param mixed $value
   *   (reference) value of variable.
   */
  public function assign_by_ref($var, &$value) {
    self::$_template->assign_by_ref($var, $value);
  }

  /**
   * Appends values to template variables.
   *
   * @param array|string $tpl_var the template variable name(s)
   * @param mixed $value
   *   The value to append.
   * @param bool $merge
   */
  public function append($tpl_var, $value = NULL, $merge = FALSE) {
    self::$_template->append($tpl_var, $value, $merge);
  }

  /**
   * Returns an array containing template variables.
   *
   * @param string $name
   *
   * @return array
   */
  public function get_template_vars($name = NULL) {
    return self::$_template->get_template_vars($name);
  }

  /**
   * Destroy all the session state of this page.
   */
  public function reset() {
    self::$_session->resetScope($this->_name);
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    return strtr(
      CRM_Utils_System::getClassName($this),
      array(
        '_' => DIRECTORY_SEPARATOR,
        '\\' => DIRECTORY_SEPARATOR,
      )
    ) . '.tpl';
  }

  /**
   * A wrapper for getTemplateFileName that includes calling the hook to
   * prevent us from having to copy & paste the logic of calling the hook
   */
  public function getHookedTemplateFileName() {
    $pageTemplateFile = $this->getTemplateFileName();
    CRM_Utils_Hook::alterTemplateFile(get_class($this), $this, 'page', $pageTemplateFile);
    return $pageTemplateFile;
  }

  /**
   * Default extra tpl file basically just replaces .tpl with .extra.tpl
   * i.e. we dont override
   *
   * @return string
   */
  public function overrideExtraTemplateFileName() {
    return NULL;
  }

  /**
   * Setter for embedded.
   *
   * @param bool $embedded
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
   */
  public function setPrint($print) {
    $this->_print = $print;
  }

  /**
   * Setter for reset.
   *
   * @param bool $reset
   */
  public function setReset($reset) {
    $this->_reset = $reset;
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
   * @return CRM_Core_Smarty
   */
  public static function &getTemplate() {
    return self::$_template;
  }

  /**
   * @param string $name
   *
   * @return null
   */
  public function getVar($name) {
    return isset($this->$name) ? $this->$name : NULL;
  }

  /**
   * @param string $name
   * @param $value
   */
  public function setVar($name, $value) {
    $this->$name = $value;
  }

}
