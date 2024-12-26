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
use Civi\Api4\MailingComponent;

/**
 * This class generates form components for Location Type.
 */
class CRM_Mailing_Form_Component extends CRM_Core_Form {

  /**
   * The id of the object being edited / created
   *
   * @var int
   */
  protected $_id;

  /**
   * The name of the BAO object for this form.
   *
   * @var string
   */
  protected $_BAOName;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->applyFilter(['name', 'subject', 'body_html'], 'trim');

    $this->add('text', 'name', ts('Name'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_BAO_MailingComponent', 'name'), TRUE
    );
    $this->addRule('name', ts('Name already exists in Database.'), 'objectExists', [
      'CRM_Mailing_BAO_MailingComponent',
      $this->_id,
    ]);

    $this->add('select', 'component_type', ts('Component Type'), CRM_Core_SelectValues::mailingComponents());

    $this->add('text', 'subject', ts('Subject'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_BAO_MailingComponent', 'subject'),
      TRUE
    );
    $this->add('textarea', 'body_text', ts('Body - TEXT Format'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_BAO_MailingComponent', 'body_text')
    );
    $this->add('textarea', 'body_html', ts('Body - HTML Format'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_BAO_MailingComponent', 'body_html')
    );

    $this->addYesNo('is_default', ts('Default?'));
    $this->addYesNo('is_active', ts('Enabled?'));

    $this->addFormRule(['CRM_Mailing_Form_Component', 'formRule']);
    $this->addFormRule(['CRM_Mailing_Form_Component', 'dataRule']);

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {

    if (isset($this->_id)) {
      $defaults = MailingComponent::get(FALSE)
        ->addWhere('id', '=', $this->_id)
        ->execute()->single();
    }
    else {
      $defaults['is_active'] = 1;
    }

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    $component = CRM_Mailing_BAO_MailingComponent::add($params);

    // set the id after save, so it can be used in a extension using the postProcess hook
    $this->_id = $component->id;

    CRM_Core_Session::setStatus(ts('The mailing component \'%1\' has been saved.', [
      1 => $component->name,
    ]), ts('Saved'), 'success');

  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @param $files
   * @param $options
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function dataRule($params, $files, $options) {
    if ($params['component_type'] == 'Header' || $params['component_type'] == 'Footer') {
      $InvalidTokens = [];
    }
    else {
      $InvalidTokens = ['action.forward' => ts("This token can only be used in send mailing context (body, header, footer)..")];
    }
    $errors = [];
    foreach (['text', 'html'] as $type) {
      $dataErrors = [];
      foreach ($InvalidTokens as $token => $desc) {
        if ($params['body_' . $type]) {
          if (preg_match('/' . preg_quote('{' . $token . '}') . '/', $params['body_' . $type])) {
            $dataErrors[] = '<li>' . ts('This message is having a invalid token - %1: %2', [
              1 => $token,
              2 => $desc,
            ]) . '</li>';
          }
        }
      }
      if (!empty($dataErrors)) {
        $errors['body_' . $type] = ts('The following errors were detected in %1 message:', [
          1 => $type,
        ]) . '<ul>' . implode('', $dataErrors) . '</ul><br /><a href="' . CRM_Utils_System::docURL2('Tokens', TRUE, NULL, NULL, NULL, "wiki") . '">' . ts('More information on tokens...') . '</a>';
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Validates that either body text or body html is required.
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @param $files
   * @param $options
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params, $files, $options) {
    $errors = [];
    if (empty($params['body_text']) && empty($params['body_html'])) {
      $errors['body_text'] = ts("Please provide either HTML or TEXT format for the Body.");
      $errors['body_html'] = ts("Please provide either HTML or TEXT format for the Body.");
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * @return array
   */
  protected function getFieldsToExcludeFromPurification(): array {
    return ['body_html'];
  }

}
