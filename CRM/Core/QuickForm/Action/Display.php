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
 * Redefine the display action.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Core_QuickForm_Action_Display extends CRM_Core_QuickForm_Action {

  /**
   * The template to display the required "red" asterick.
   * @var string
   */
  static $_requiredTemplate = NULL;

  /**
   * The template to display error messages inline with the form element.
   * @var string
   */
  static $_errorTemplate = NULL;

  /**
   * Class constructor.
   *
   * @param object $stateMachine
   *   Reference to state machine object.
   *
   * @return \CRM_Core_QuickForm_Action_Display
   */
  public function __construct(&$stateMachine) {
    parent::__construct($stateMachine);
  }

  /**
   * Processes the request.
   *
   * @param CRM_Core_Form $page
   *   CRM_Core_Form the current form-page.
   * @param string $actionName
   *   Current action name, as one Action object can serve multiple actions.
   *
   * @return object|void
   */
  public function perform(&$page, $actionName) {
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
   * Render the page using a custom templating system.
   *
   * @param CRM_Core_Form $page
   *   The CRM_Core_Form page.
   */
  public function renderForm(&$page) {
    $this->_setRenderTemplates($page);
    $template = CRM_Core_Smarty::singleton();
    $form = $page->toSmarty();

    // Deprecated - use snippet=6 instead of json=1
    $json = CRM_Utils_Request::retrieve('json', 'Boolean', CRM_Core_DAO::$_nullObject);
    if ($json) {
      CRM_Utils_JSON::output($form);
    }

    $template->assign('form', $form);
    $template->assign('isForm', 1);

    $controller = &$page->controller;
    // Stop here if we are in embedded mode. Exception: displaying form errors via ajax
    if ($controller->getEmbedded() && !(!empty($form['errors']) && $controller->_QFResponseType == 'json')) {
      return;
    }

    $template->assign('action', $page->getAction());

    $pageTemplateFile = $page->getHookedTemplateFileName();
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
      if (!empty($page->ajaxResponse)) {
        $response += $page->ajaxResponse;
      }
      if (!empty($form['errors'])) {
        $response['status'] = 'form_error';
        $response['errors'] = $form['errors'];
      }
      CRM_Core_Page_AJAX::returnJsonResponse($response);
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
   * Set the various rendering templates.
   *
   * @param CRM_Core_Form $page
   *   The CRM_Core_Form page.
   */
  public function _setRenderTemplates(&$page) {
    if (self::$_requiredTemplate === NULL) {
      $this->initializeTemplates();
    }

    $renderer = &$page->getRenderer();

    $renderer->setRequiredTemplate(self::$_requiredTemplate);
    $renderer->setErrorTemplate(self::$_errorTemplate);
  }

  /**
   * Initialize the various templates.
   */
  public function initializeTemplates() {
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
