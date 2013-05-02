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
 * Redefine the display action.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Core_QuickForm_Action_Display extends CRM_Core_QuickForm_Action {

  /**
   * the template to display the required "red" asterick
   * @var string
   */
  static $_requiredTemplate = NULL;

  /**
   * the template to display error messages inline with the form element
   * @var string
   */
  static $_errorTemplate = NULL;

  /**
   * class constructor
   *
   * @param object $stateMachine reference to state machine object
   *
   * @return object
   * @access public
   */
  function __construct(&$stateMachine) {
    parent::__construct($stateMachine);
  }

  /**
   * Processes the request.
   *
   * @param  object    $page       CRM_Core_Form the current form-page
   * @param  string    $actionName Current action name, as one Action object can serve multiple actions
   *
   * @return void
   * @access public
   */
  function perform(&$page, $actionName) {
    $pageName = $page->getAttribute('id');

    // If the original action was 'display' and we have values in container then we load them
    // BTW, if the page was invalid, we should later call validate() to get the errors
    list(, $oldName) = $page->controller->getActionName();
    if ('display' == $oldName) {
      // If the controller is "modal" we should not allow direct access to a page
      // unless all previous pages are valid (see also bug #2323)
      if ($page->controller->isModal() && !$page->controller->isValid($page->getAttribute('id'))) {
        $target = &$page->controller->getPage($page->controller->findInvalid());
        return $target->handle('jump');
      }
      $data = &$page->controller->container();
      if (!empty($data['values'][$pageName])) {
        $page->loadValues($data['values'][$pageName]);
        $validate = FALSE === $data['valid'][$pageName];
      }
    }

    // set "common" defaults and constants
    $page->controller->applyDefaults($pageName);
    $page->isFormBuilt() or $page->buildForm();
    // if we had errors we should show them again
    if (isset($validate) && $validate) {
      $page->validate();
    }
    //will this work generally as TRUE (i.e., return output)
    //was default, i.e., FALSE
    return $this->renderForm($page);
  }

  /**
   * render the page using a custom templating
   * system
   *
   * @param object  $page the CRM_Core_Form page
   * @param boolean $ret  should we echo or return output
   *
   * @return void
   * @access public
   */
  function renderForm(&$page) {
    $this->_setRenderTemplates($page);
    $template = CRM_Core_Smarty::singleton();
    $form = $page->toSmarty();

    $json = CRM_Utils_Request::retrieve('json', 'Boolean', CRM_Core_DAO::$_nullObject);

    if ($json) {
      echo json_encode($form);
      CRM_Utils_System::civiExit();
    }

    $template->assign('form', $form);
    $template->assign('isForm', 1);

    $controller = &$page->controller;
    if ($controller->getEmbedded()) {
      return;
    }

    $template->assign('action', $page->getAction());

    $pageTemplateFile = $page->getTemplateFileName();
    $template->assign('tplFile', $pageTemplateFile);

    $content = $template->fetch($controller->getTemplateFile());

    if (!defined('CIVICRM_UF_HEAD') && $region = CRM_Core_Region::instance('html-header', FALSE)) {
      CRM_Utils_System::addHTMLHead($region->render(''));
    }
    CRM_Utils_System::appendTPLFile($pageTemplateFile,
      $content,
      $page->overrideExtraTemplateFileName()
    );

    //its time to call the hook.
    CRM_Utils_Hook::alterContent($content, 'form', $pageTemplateFile, $page);

    $print = $controller->getPrint();
    if ($print) {
      $html = &$content;
    }
    else {
      $html = CRM_Utils_System::theme($content, $print);
    }

    if ($controller->_QFResponseType == 'json') {
      $response = array('content' => $html);
      // CRM-11831 @see http://www.malsup.com/jquery/form/#file-upload
      $xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
      if (!$xhr) {
        echo '<textarea>';
      }
      echo json_encode($response);
      if (!$xhr) {
        echo '</textarea>';
      }
      CRM_Utils_System::civiExit();
    }

    if ($print) {
      if ($print == CRM_Core_Smarty::PRINT_PDF) {
        CRM_Utils_PDF_Utils::html2pdf(
          $content,
          "{$page->_name}.pdf",
          FALSE,
          array('paper_size' => 'a3', 'orientation' => 'landscape')
        );
      }
      else {
        echo $html;
      }
      CRM_Utils_System::civiExit();
    }

    print $html;
  }

  /**
   * set the various rendering templates
   *
   * @param object  $page the CRM_Core_Form page
   *
   * @return void
   * @access public
   */
  function _setRenderTemplates(&$page) {
    if (self::$_requiredTemplate === NULL) {
      $this->initializeTemplates();
    }

    $renderer = &$page->getRenderer();

    $renderer->setRequiredTemplate(self::$_requiredTemplate);
    $renderer->setErrorTemplate(self::$_errorTemplate);
  }

  /**
   * initialize the various templates
   *
   * @param object  $page the CRM_Core_Form page
   *
   * @return void
   * @access public
   */
  function initializeTemplates() {
    if (self::$_requiredTemplate !== NULL) {
      return;
    }

    $config = CRM_Core_Config::singleton();

    $templateDir = $config->templateDir;
    if (is_array($templateDir)) {
      $templateDir = array_pop($templateDir);
    }

    self::$_requiredTemplate = file_get_contents($templateDir . '/CRM/Form/label.tpl');
    self::$_errorTemplate = file_get_contents($templateDir . '/CRM/Form/error.tpl');
  }
}

