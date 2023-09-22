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

  public function getFirstMailBody() : string {
    return $this->getFirstMail()['body'] ?? '';
  }

  private $redirects;

  private $mailSpoolID;

  private $validation;

  private $originalMailSetting;

  public const CONSTRUCTED = 0;
  public const PREPROCESSED = 1;
  public const BUILT = 3;
  public const VALIDATED = 4;
  public const SUBMITTED = 5;

  /**
   * @var \CRM_Contribute_Import_Controller|\CRM_Core_Controller|\CRM_Event_Controller_Registration
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
    if ($state > self::CONSTRUCTED) {
      $this->form->preProcess();
    }
    if ($state > self::PREPROCESSED) {
      $this->form->buildForm();
    }
    if ($state > self::BUILT) {
      $this->validation = $this->form->validate();
    }
    if ($state > self::VALIDATED) {
      $this->postProcess();
    }
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
    /* @var \CRM_Core_Form */
    $form = new $formName();
    $form->controller = $this->form->controller;
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
      return call_user_func([$this->form, $name], $arguments);
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
    $this->form->postProcess();
    foreach ($this->subsequentForms as $form) {
      $form->preProcess();
      $form->buildForm();
      $form->postProcess();
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
    $_REQUEST += $urlParameters;
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
        $this->form->controller = new \CRM_Contact_Import_Controller();
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
          $this->form->controller = new \CRM_Contribute_Import_Controller();
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
        $this->form->controller = new \CRM_Member_Import_Controller();
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
        $this->form->controller = new \CRM_Event_Import_Controller();
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
        $this->form->controller = new \CRM_Activity_Import_Controller();
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
        $this->form->controller = new \CRM_Custom_Import_Controller();
        $this->form->controller->setStateMachine(new \CRM_Core_StateMachine($this->form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $this->form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return;

      case strpos($class, 'Search') !== FALSE:
        $this->form->controller = new \CRM_Contact_Controller_Search();
        break;

      case strpos($class, '_Form_') !== FALSE:
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

}
