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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

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
    $this->_id = $this->get('id');
    $this->_BAOName = $this->get('BAOName');
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
      ]
    );
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = [];
    $params = [];

    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      $baoName = $this->_BAOName;
      $baoName::retrieve($params, $defaults);
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
    CRM_Core_Session::setStatus(ts('The mailing component \'%1\' has been saved.', [
        1 => $component->name,
      ]
    ), ts('Saved'), 'success');

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
    foreach ([
               'text',
               'html',
             ] as $type) {
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

}
