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

namespace Civi\Test;

use Civi\Api4\Utils\ReflectionUtils;

class FormWrapper {

  /**
   * @var array
   */
  private $formValues;

  /**
   * @var \CRM_Core_Form
   */
  protected $form;

  /**
   * @var \CRM_Core_Form[]
   */
  protected $subsequentForms = [];

  private $output;

  private $templateVariables;

  private $mail;

  private $exception;

  /**
   * @return \Exception
   */
  public function getException() {
    return $this->exception;
  }

  /**
   * @return null|array
   */
  public function getMail(): ?array {
    return $this->mail;
  }

  /**
   * @return array
   */
  public function getFirstMail(): array {
    return $this->mail ? (array) reset($this->mail) : [];
  }

  /**
   * Get the number of emails sent.
   *
   * @return int
   */
  public function getMailCount(): int {
    return count((array) $this->mail);
  }

  public function getFirstMailBody() : string {
    return $this->getFirstMail()['body'] ?? '';
  }

  /**
   * @return array
   */
  public function getTemplateVariables(): array {
    return $this->templateVariables;
  }

  /**
   * Get a variable assigned to the template.
   *
   * @return mixed
   */
  public function getTemplateVariable($string) {
    return $this->templateVariables[$string];
  }

  private $redirects;

  private $mailSpoolID;

  /**
   * @var array|bool
   */
  private $validation;

  /**
   * @return array|bool
   */
  public function getValidationOutput() {
    return $this->validation;
  }

  private $originalMailSetting;

  public const CONSTRUCTED = 0;
  public const PREPROCESSED = 1;
  public const BUILT = 3;
  public const VALIDATED = 4;
  public const SUBMITTED = 5;

  /**
   * @var \CRM_Import_Controller|\CRM_Core_Controller|\CRM_Event_Controller_Registration
   */
  private $formController;

  /**
   * @param string $formName
   * @param array $formValues
   * @param array $urlParameters
   */
  public function __construct(string $formName, array $formValues = [], array $urlParameters = []) {
    $this->formValues = $formValues;
    $this->setFormObject($formName, $this->formValues, $urlParameters);
  }

  /**
   * Process a CiviCRM form.
   *
   * @param int $state
   *
   * @return \Civi\Test\FormWrapper
   */
  public function processForm(int $state = self::SUBMITTED): self {
    \CRM_Core_Smarty::singleton()->pushScope([]);
    if ($state > self::CONSTRUCTED) {
      $this->form->preProcess();
    }
    if ($state > self::PREPROCESSED) {
      $this->form->buildForm();
    }
    if ($state > self::BUILT) {
      $this->form->validate();
      $this->validation = $this->form->_errors;
    }
    if ($state > self::VALIDATED) {
      $this->postProcess();
    }
    $this->templateVariables = \CRM_Core_Smarty::singleton()->getTemplateVars();
    \CRM_Core_Smarty::singleton()->popScope([]);
    return $this;
  }

  /**
   * Add another form to process.
   *
   * @param string $formName
   * @param array $formValues
   *
   * @return $this
   */
  public function addSubsequentForm(string $formName, array $formValues = []): FormWrapper {
    /* @var \CRM_Core_Form $form */
    $form = new $formName();
    $form->controller = $this->form->controller;
    $form->_submitValues = $formValues;
    $form->controller->addPage($form);
    $_SESSION['_' . $this->form->controller->_name . '_container']['values'][$form->getName()] = $formValues;
    $this->subsequentForms[$form->getName()] = $form;
    return $this;
  }

  /**
   * Call a function declared as externally accessible on the form.
   *
   * This will only call a limited number of form functions that are either
   * a) supported from use from outside of core or
   * b) direct form flow functions
   *
   * As this class is expected to be used in extension tests we don't want to
   * perpetuate the current issue with internal / unsupported properties being
   * accessed willy nilly - so this provides testing support using supported
   * or intrinsic functions only.
   *
   * @param string $name
   * @param array $arguments
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   * @throws \ReflectionException
   */
  public function __call(string $name, array $arguments) {
    if (!empty(ReflectionUtils::getCodeDocs((new \ReflectionMethod($this->form, $name)), 'Method')['api'])) {
      return call_user_func_array([$this->form, $name], $arguments);
    }
    throw new \CRM_Core_Exception($name . ' method not supported for external use');
  }

  /**
   * Call form post process function.
   *
   * @return $this
   */
  public function postProcess(): self {
    $this->startTrackingMail();
    try {
      $this->form->postProcess();
      foreach ($this->subsequentForms as $form) {
        $form->preProcess();
        $form->buildForm();
        $form->validate();
        $this->validation[$form->getName()] = $form->_errors;
        $form->postProcess();
      }
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
      $this->exception = $e;
    }
    $this->stopTrackingMail();
    return $this;
  }

  /**
   * Instantiate form object.
   *
   * We need to instantiate the form to run preprocess, which means we have to trick it about the request method.
   *
   * @param string $class
   *   Name of form class.
   *
   * @param array $formValues
   *
   * @param array $urlParameters
   */
  private function setFormObject(string $class, array $formValues = [], array $urlParameters = []): void {
    $_POST = $formValues;
    $this->form = new $class();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_REQUEST = array_merge($_REQUEST, $urlParameters);
    switch ($class) {
      case 'CRM_Event_Cart_Form_Checkout_Payment':
      case 'CRM_Event_Cart_Form_Checkout_ParticipantsAndPrices':
        $this->form->controller = new \CRM_Event_Cart_Controller_Checkout();
        break;

      case 'CRM_Event_Form_Registration_Register':
        $this->form->controller = $this->formController = new \CRM_Event_Controller_Registration();
        break;

      case 'CRM_Event_Form_Registration_Confirm':
      case 'CRM_Event_Form_Registration_AdditionalParticipant':
        if ($this->formController) {
          // Add to the existing form controller.
          $this->form->controller = $this->formController;
        }
        else {
          $this->form->controller = $this->formController = new \CRM_Event_Controller_Registration();
        }
        break;

      case 'CRM_Contribute_Form_Contribution_Main':
        $this->form->controller = new \CRM_Contribute_Controller_Contribution();
        break;

      case 'CRM_Contribute_Form_Contribution_Confirm':
        $this->form->controller = new \CRM_Contribute_Controller_Contribution();
        $this->form->controller->setStateMachine(new \CRM_Contribute_StateMachine_Contribution($this->form->controller));
        // The submitted values are on the Main form.
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['Main'] = $formValues;
        return;

      case 'CRM_Contact_Import_Form_DataSource':
      case 'CRM_Contact_Import_Form_MapField':
      case 'CRM_Contact_Import_Form_Preview':
        $this->form->controller = new \CRM_Import_Controller('Contact import', ['entity' => 'Contact']);
        $this->form->controller->setStateMachine(new \CRM_Core_StateMachine($this->form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return;

      case 'CRM_Contribute_Import_Form_DataSource':
      case 'CRM_Contribute_Import_Form_MapField':
      case 'CRM_Contribute_Import_Form_Preview':
        if ($this->formController) {
          // Add to the existing form controller.
          $this->form->controller = $this->formController;
        }
        else {
          $this->form->controller = new \CRM_Import_Controller('Contribution import', ['entity' => 'Contribution']);
          $this->form->controller->setStateMachine(new \CRM_Core_StateMachine($this->form->controller));
          $this->formController = $this->form->controller;
        }
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return;

      case 'CRM_Member_Import_Form_DataSource':
      case 'CRM_Member_Import_Form_MapField':
      case 'CRM_Member_Import_Form_Preview':
        $this->form->controller = new \CRM_Import_Controller('Membership import', ['entity' => 'Membership']);
        $this->form->controller->setStateMachine(new \CRM_Core_StateMachine($this->form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['DataSource'] = $this->formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return;

      case 'CRM_Event_Import_Form_DataSource':
      case 'CRM_Event_Import_Form_MapField':
      case 'CRM_Event_Import_Form_Preview':
        $this->form->controller = new \CRM_Import_Controller('Participant import', ['entity' => 'Participant']);
        $this->form->controller->setStateMachine(new \CRM_Core_StateMachine($this->form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return;

      case 'CRM_Activity_Import_Form_DataSource':
      case 'CRM_Activity_Import_Form_MapField':
      case 'CRM_Activity_Import_Form_Preview':
        $this->form->controller = new \CRM_Import_Controller('Activity import', ['entity' => 'Activity']);
        $this->form->controller->setStateMachine(new \CRM_Core_StateMachine($this->form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return;

      case 'CRM_Custom_Import_Form_DataSource':
      case 'CRM_Custom_Import_Form_MapField':
      case 'CRM_Custom_Import_Form_Preview':
        $this->form->controller = new \CRM_Import_Controller('Custom Value import', ['class_prefix' => 'CRM_Custom_Import']);
        $this->form->controller->setStateMachine(new \CRM_Core_StateMachine($this->form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return;

      case $class === 'CRM_Contact_Form_Search_Basic':
        $this->form->controller = new \CRM_Contact_Controller_Search('Basic', TRUE, \CRM_Core_Action::BASIC);
        $this->form->setAction(\CRM_Core_Action::BASIC);
        break;

      case str_contains($class, 'Search'):
        $this->form->controller = new \CRM_Contact_Controller_Search();
        if ($class === 'CRM_Contact_Form_Search_Basic') {
          $this->form->setAction(\CRM_Core_Action::BASIC);
        }
        break;

      case str_contains($class, '_Form_'):
        $this->form->controller = new \CRM_Core_Controller_Simple($class, $this->form->getName());
        break;

      default:
        $this->form->controller = new \CRM_Core_Controller();
    }

    $this->form->controller->setStateMachine(new \CRM_Core_StateMachine($this->form->controller));
    $_SESSION['_' . $this->form->controller->_name . '_container']['values'][$this->form->getName()] = $formValues;
    if (isset($formValues['_qf_button_name'])) {
      $_SESSION['_' . $this->form->controller->_name . '_container']['_qf_button_name'] = $this->formValues['_qf_button_name'];
    }
  }

  /**
   * Start tracking any emails sent by this form.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  private function startTrackingMail(): void {
    $this->originalMailSetting = \Civi::settings()->get('mailing_backend');
    \Civi::settings()
      ->set('mailing_backend', array_merge((array) $this->originalMailSetting, ['outBound_option' => \CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB]));
    $this->mailSpoolID = (int) \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_spool');
  }

  /**
   * Store any mails sent & revert to pre-test behaviour.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  private function stopTrackingMail(): void {
    $dao = \CRM_Core_DAO::executeQuery('SELECT headers, body FROM civicrm_mailing_spool WHERE id > ' . $this->mailSpoolID . ' ORDER BY id');
    \CRM_Core_DAO::executeQuery('DELETE FROM civicrm_mailing_spool WHERE id > ' . $this->mailSpoolID);
    while ($dao->fetch()) {
      $this->mail[] = ['headers' => $dao->headers, 'body' => $dao->body];
    }
    \Civi::settings()->set('mailing_backend', $this->originalMailSetting);
  }

  /**
   * Retrieve a deprecated property, ensuring a deprecation notice is thrown.
   *
   * @param string $property
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function getDeprecatedProperty(string $property) {
    try {
      $this->form->$property;
    }
    catch (\Exception $e) {
      $oldErrorLevel = error_reporting(0);
      $value = $this->form->$property;
      error_reporting($oldErrorLevel);
      return $value;
    }
    throw new \CRM_Core_Exception('Deprecation should have been triggered');
  }

  /**
   * @param string $name
   * @param mixed $value
   *
   * @throws \CRM_Core_Exception
   */
  public function checkTemplateVariable(string $name, $value): void {
    $actual = $this->templateVariables[$name];
    if ($this->templateVariables[$name] !== $value) {
      $differences = [];
      if (is_array($value)) {
        foreach ($value as $key => $expectedItem) {
          $actualItem = $this->templateVariables[$name][$key];
          if ($expectedItem !== $actualItem) {
            $differences[] = $key;
          }
        }
      }
      throw new \CRM_Core_Exception("Template variable $name expected " . print_r($value, TRUE) . ' actual value: ' . print_r($this->templateVariables[$name], TRUE) . ($differences ? 'differences in ' . implode(',', $differences) : ''));
    }
  }

}
