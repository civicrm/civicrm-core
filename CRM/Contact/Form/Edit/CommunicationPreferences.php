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
 * Form helper class for an Communication Preferences object.
 */
class CRM_Contact_Form_Edit_CommunicationPreferences {

  /**
   * Greetings.
   *
   * @var array
   */
  static $greetings = [];

  /**
   * Build the form object elements for Communication Preferences object.
   *
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   */
  public static function buildQuickForm(&$form) {
    // since the pcm - preferred communication method is logically
    // grouped hence we'll use groups of HTML_QuickForm

    // checkboxes for DO NOT phone, email, mail
    // we take labels from SelectValues
    $privacy = $commPreff = $commPreference = [];
    $privacyOptions = CRM_Core_SelectValues::privacy();

    // we add is_opt_out as a separate checkbox below for display and help purposes so remove it here
    unset($privacyOptions['is_opt_out']);

    foreach ($privacyOptions as $name => $label) {
      $privacy[] = $form->createElement('advcheckbox', $name, NULL, $label);
    }
    $form->addGroup($privacy, 'privacy', ts('Privacy'), '&nbsp;<br/>');

    // preferred communication method
    $comm = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method', ['loclize' => TRUE]);
    foreach ($comm as $value => $title) {
      $commPreff[] = $form->createElement('advcheckbox', $value, NULL, $title);
    }
    $form->addField('preferred_communication_method', ['entity' => 'contact', 'type' => 'CheckBoxGroup']);
    $form->addField('preferred_language', ['entity' => 'contact']);

    if (!empty($privacyOptions)) {
      $commPreference['privacy'] = $privacyOptions;
    }
    if (!empty($comm)) {
      $commPreference['preferred_communication_method'] = $comm;
    }

    //using for display purpose.
    $form->assign('commPreference', $commPreference);

    $form->addField('preferred_mail_format', ['entity' => 'contact', 'label' => ts('Email Format')]);

    $form->addField('is_opt_out', ['entity' => 'contact', 'label' => ts('NO BULK EMAILS (User Opt Out)')]);

    $form->addField('communication_style_id', ['entity' => 'contact', 'type' => 'RadioGroup']);
    //check contact type and build filter clause accordingly for greeting types, CRM-4575
    $greetings = self::getGreetingFields($form->_contactType);

    foreach ($greetings as $greeting => $fields) {
      $filter = [
        'contact_type' => $form->_contactType,
        'greeting_type' => $greeting,
      ];

      //add addressee in Contact form
      $greetingTokens = CRM_Core_PseudoConstant::greeting($filter);
      if (!empty($greetingTokens)) {
        $form->addElement('select', $fields['field'], $fields['label'],
          [
            '' => ts('- select -'),
          ] + $greetingTokens
        );
        //custom addressee
        $form->addElement('text', $fields['customField'], $fields['customLabel'],
          CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', $fields['customField']), $fields['js']
        );
      }
    }
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param CRM_Contact_Form_Edit_CommunicationPreferences $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    //CRM-4575

    $greetings = self::getGreetingFields($self->_contactType);
    foreach ($greetings as $greeting => $details) {
      $customizedValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', $details['field'], 'Customized');
      if (CRM_Utils_Array::value($details['field'], $fields) == $customizedValue && empty($fields[$details['customField']])) {
        $errors[$details['customField']] = ts('Custom  %1 is a required field if %1 is of type Customized.',
          [1 => $details['label']]
        );
      }
    }

    if (array_key_exists('preferred_mail_format', $fields) && empty($fields['preferred_mail_format'])) {
      $errors['preferred_mail_format'] = ts('Please select an email format preferred by this contact.');
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set default values for the form.
   *
   * @param CRM_Core_Form $form
   * @param array $defaults
   */
  public static function setDefaultValues(&$form, &$defaults) {

    if (!empty($defaults['preferred_language'])) {
      $languages = CRM_Contact_BAO_Contact::buildOptions('preferred_language');
      $defaults['preferred_language'] = CRM_Utils_Array::key($defaults['preferred_language'], $languages);
    }

    // CRM-7119: set preferred_language to default if unset
    if (empty($defaults['preferred_language'])) {
      $config = CRM_Core_Config::singleton();
      $defaults['preferred_language'] = $config->lcMessages;
    }

    if (empty($defaults['communication_style_id'])) {
      $defaults['communication_style_id'] = array_pop(CRM_Core_OptionGroup::values('communication_style', TRUE, NULL, NULL, 'AND is_default = 1'));
    }

    // CRM-17778 -- set preferred_mail_format to default if unset
    if (empty($defaults['preferred_mail_format'])) {
      $defaults['preferred_mail_format'] = 'Both';
    }
    else {
      $defaults['preferred_mail_format'] = array_search($defaults['preferred_mail_format'], CRM_Core_SelectValues::pmf());
    }

    //set default from greeting types CRM-4575, CRM-9739
    if ($form->_action & CRM_Core_Action::ADD) {
      foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
        if (empty($defaults[$greeting . '_id'])) {
          if ($defaultGreetingTypeId = CRM_Contact_BAO_Contact_Utils::defaultGreeting($form->_contactType, $greeting)
          ) {
            $defaults[$greeting . '_id'] = $defaultGreetingTypeId;
          }
        }
      }
    }
    else {
      foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
        $name = "{$greeting}_display";
        $form->assign($name, CRM_Utils_Array::value($name, $defaults));
      }
    }
  }

  /**
   *  Set array of greeting fields.
   *
   * @param string $contactType
   */
  public static function getGreetingFields($contactType) {
    if (empty(self::$greetings[$contactType])) {
      self::$greetings[$contactType] = [];

      $js = [
        'onfocus' => "if (!this.value) {  this.value='Dear ';} else return false",
        'onblur' => "if ( this.value == 'Dear') {  this.value='';} else return false",
      ];

      self::$greetings[$contactType] = [
        'addressee' => [
          'field' => 'addressee_id',
          'customField' => 'addressee_custom',
          'label' => ts('Addressee'),
          'customLabel' => ts('Custom Addressee'),
          'js' => NULL,
        ],
        'email_greeting' => [
          'field' => 'email_greeting_id',
          'customField' => 'email_greeting_custom',
          'label' => ts('Email Greeting'),
          'customLabel' => ts('Custom Email Greeting'),
          'js' => $js,
        ],
        'postal_greeting' => [
          'field' => 'postal_greeting_id',
          'customField' => 'postal_greeting_custom',
          'label' => ts('Postal Greeting'),
          'customLabel' => ts('Custom Postal Greeting'),
          'js' => $js,
        ],
      ];
    }

    return self::$greetings[$contactType];
  }

}
