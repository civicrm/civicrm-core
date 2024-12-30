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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for processing a survey.
 */
class CRM_Campaign_Form_Survey extends CRM_Core_Form {
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * The id of the object being edited.
   *
   * @var int
   *
   * @internal
   */
  protected $_surveyId;

  /**
   * Action.
   *
   * @var int
   */
  public $_action;

  /**
   * SurveyTitle.
   *
   * @var string
   */
  protected $_surveyTitle;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Survey';
  }

  /**
   * Get the entity id being edited.
   *
   * @internal
   *
   * @return int|null
   */
  public function getEntityId() {
    return $this->_surveyId;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    // Multistep form doesn't play well with popups
    $this->preventAjaxSubmit();

    if (!CRM_Campaign_BAO_Campaign::accessCampaign()) {
      CRM_Utils_System::permissionDenied();
    }

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add', 'REQUEST');
    if ($this->getSurveyID()) {
      $this->_single = TRUE;

      $params = ['id' => $this->_surveyId];
      CRM_Campaign_BAO_Survey::retrieve($params, $surveyInfo);
      $this->_surveyTitle = $surveyInfo['title'];
      $this->assign('surveyTitle', $this->_surveyTitle);
      $this->setTitle(ts('Configure Survey - %1', [1 => $this->_surveyTitle]));
    }

    $this->assign('action', $this->_action);
    $this->assign('surveyId', $this->getSurveyID());

    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Survey', array_filter([
        'id' => $this->getSurveyID(),
      ]));
    }

    // CRM-11480, CRM-11682
    // Preload libraries required by the "Questions" tab
    $this->assign('perm', (bool) CRM_Core_Permission::check('administer CiviCRM'));
    CRM_UF_Page_ProfileEditor::registerProfileScripts();
    CRM_UF_Page_ProfileEditor::registerSchemas(['IndividualModel', 'ActivityModel']);

    $this->build();
  }

  /**
   * Get the survey ID.
   *
   * @api supported for external use.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getSurveyID(): ?int {
    if (!isset($this->_surveyId)) {
      $this->_surveyId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }
    return $this->_surveyId;
  }

  /**
   * Build tab header.
   *
   * @return array
   */
  private function build() {
    $form = $this;
    $tabs = $form->get('tabHeader');
    if (!$tabs || empty($_GET['reset'])) {
      $tabs = $this->processSurveyForm() ?? [];
      $form->set('tabHeader', $tabs);
    }
    $tabs = \CRM_Core_Smarty::setRequiredTabTemplateKeys($tabs);
    $form->assign('tabHeader', $tabs);
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header')
      ->addSetting([
        'tabSettings' => [
          'active' => $this->getCurrentTab($tabs),
        ],
      ]);
    return $tabs;
  }

  /**
   * @param array $tabs
   *
   * @return int|string
   */
  private function getCurrentTab($tabs) {
    static $current = FALSE;

    if ($current) {
      return $current;
    }

    if (is_array($tabs)) {
      foreach ($tabs as $subPage => $pageVal) {
        if ($pageVal['current'] === TRUE) {
          $current = $subPage;
          break;
        }
      }
    }

    $current = $current ?: 'main';
    return $current;
  }

  /**
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function processSurveyForm() {
    $form = $this;
    if ($this->getSurveyID() <= 0) {
      return NULL;
    }

    $tabs = [
      'main' => [
        'title' => ts('Main Information'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'questions' => [
        'title' => ts('Questions'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'results' => [
        'title' => ts('Results'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
    ];

    $surveyID = $this->getSurveyID();
    $class = $this->_name;
    $class = CRM_Utils_String::getClassName($class);
    $class = strtolower($class);

    if (array_key_exists($class, $tabs)) {
      $tabs[$class]['current'] = TRUE;
      $qfKey = $form->get('qfKey');
      if ($qfKey) {
        $tabs[$class]['qfKey'] = "&qfKey={$qfKey}";
      }
    }

    if ($surveyID) {
      $reset = !empty($_GET['reset']) ? 'reset=1&' : '';

      foreach ($tabs as $key => $value) {
        if (!isset($tabs[$key]['qfKey'])) {
          $tabs[$key]['qfKey'] = NULL;
        }

        $tabs[$key]['link'] = CRM_Utils_System::url("civicrm/survey/configure/{$key}",
          "{$reset}action=update&id={$surveyID}{$tabs[$key]['qfKey']}"
        );
        $tabs[$key]['active'] = $tabs[$key]['valid'] = TRUE;
      }
    }
    return $tabs;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    $session = CRM_Core_Session::singleton();
    if ($this->_surveyId) {
      $buttons = [
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
      ];
    }
    else {
      $buttons = [
        [
          'type' => 'upload',
          'name' => ts('Continue'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
      ];
    }
    $buttons[] = [
      'type' => 'cancel',
      'name' => ts('Cancel'),
    ];
    $this->addButtons($buttons);

    $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
    $session->replaceUserContext($url);
  }

  public function endPostProcess() {
    // make submit buttons keep the current working tab opened.
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $tabTitle = $className = CRM_Utils_String::getClassName($this->_name);
      if ($tabTitle === 'Main') {
        $tabTitle = 'Main settings';
      }
      $subPage = strtolower($className);
      CRM_Core_Session::setStatus(ts("'%1' have been saved.", [1 => $tabTitle]), ts('Saved'), 'success');

      $this->postProcessHook();

      if ($this->_action & CRM_Core_Action::ADD) {
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/survey/configure/questions",
          "action=update&reset=1&id={$this->_surveyId}"));
      }
      if ($this->controller->getButtonName('submit') === "_qf_{$className}_upload_done") {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey'));
      }
      elseif ($this->controller->getButtonName('submit') === "_qf_{$className}_upload_next") {
        $subPage = $this->getNextTab();
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/survey/configure/{$subPage}",
          "action=update&reset=1&id={$this->_surveyId}"));
      }
      else {
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/survey/configure/{$subPage}",
          "action=update&reset=1&id={$this->_surveyId}"));
      }
    }
  }

  /**
   * @return string
   */
  public function getTemplateFileName(): string {
    if ($this->_surveyId <= 0 || $this->controller->getPrint()) {
      return parent::getTemplateFileName();
    }
    // hack lets suppress the form rendering for now
    self::$_template->assign('isForm', FALSE);
    return 'CRM/Campaign/Form/Survey/Tab.tpl';
  }

  /**
   *
   * @return int|string
   */
  private function getNextTab() {
    $form = $this;
    static $next = FALSE;
    if ($next) {
      return $next;
    }

    $tabs = $form->get('tabHeader');
    if (is_array($tabs)) {
      $current = FALSE;
      foreach ($tabs as $subPage => $pageVal) {
        if ($current) {
          $next = $subPage;
          break;
        }
        if ($pageVal['current'] === TRUE) {
          $current = $subPage;
        }
      }
    }

    $next = $next ?: 'main';
    return $next;
  }

}
