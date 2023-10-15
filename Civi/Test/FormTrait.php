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

use Civi\Test\FormWrappers\EventFormOnline;
use Civi\Test\FormWrappers\EventFormParticipant;

/**
 * Trait for writing tests interacting with QuickForm.
 */
trait FormTrait {

  /**
   * @var \Civi\Test\FormWrapper
   */
  private $form;

  /**
   * @param $formName
   * @param $submittedValues
   * @param array $urlParameters
   *
   * @return \Civi\Test\FormWrapper
   */
  public function getTestForm($formName, $submittedValues, array $urlParameters = []) {
    $this->form = NULL;
    if ($formName === 'CRM_Event_Form_Participant') {
      $this->form = new EventFormParticipant($formName, $submittedValues, $urlParameters);
    }
    if ($formName === 'CRM_Event_Form_Registration_Register') {
      $this->form = new EventFormOnline($formName, $submittedValues, $urlParameters);
    }
    if (!$this->form) {
      $this->form = new FormWrapper($formName, $submittedValues, $urlParameters);
    }
    return $this->form;
  }

  /**
   * Assert that the sent mail included the supplied strings.
   *
   * @param array $strings
   * @param int $mailIndex
   */
  protected function assertMailSentContainingStrings(array $strings, int $mailIndex = 0): void {
    foreach ($strings as $string) {
      $this->assertMailSentContainingString($string, $mailIndex);
    }
  }

  /**
   * Assert that the sent mail included the supplied string.
   *
   * @param string $string
   * @param int $mailIndex
   */
  protected function assertMailSentContainingString(string $string, int $mailIndex = 0): void {
    $mail = $this->form->getMail()[$mailIndex];
    $this->assertStringContainsString($string, $mail['body']);
  }

  /**
   * Assert that the sent mail included the supplied strings.
   *
   * @param array $recipients
   * @param int $mailIndex
   */
  protected function assertMailSentTo(array $recipients, int $mailIndex = 0): void {
    $mail = $this->form->getMail()[$mailIndex];
    foreach ($recipients as $string) {
      $this->assertStringContainsString($string, $mail['headers']);
    }
  }

  /**
   * Retrieve a deprecated property, ensuring a deprecation notice is thrown.
   *
   * @param string $property
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  protected function getDeprecatedProperty(string $property) {
    return $this->form->getDeprecatedProperty($property);
  }

}
