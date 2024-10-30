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
   * Assert that the sent mail included the supplied string.
   *
   * @param array $errors
   */
  protected function assertValidationError(array $errors): void {
    $this->assertEquals($errors, $this->form->getValidationOutput());
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
   * Assert that the sent mail included the supplied strings.
   *
   * @param array $strings
   * @param int $mailIndex
   */
  protected function assertMailSentNotContainingStrings(array $strings, int $mailIndex = 0): void {
    foreach ($strings as $string) {
      $this->assertMailSentNotContainingString($string, $mailIndex);
    }
  }

  /**
   * Assert that the sent mail included the supplied string.
   *
   * @param string $string
   * @param int $mailIndex
   */
  protected function assertMailSentContainingString(string $string, int $mailIndex = 0): void {
    if (!$this->form->getMail()) {
      $this->fail('No mail sent');
    }
    $mail = $this->form->getMail()[$mailIndex];
    $this->assertStringContainsString(preg_replace('/\s+/', '', $string), preg_replace('/\s+/', '', $mail['body']), 'String not found: ' . $string . "\n" . $mail['body']);
  }

  /**
   * Assert that the sent mail included the supplied string.
   *
   * @param string $string
   * @param int $mailIndex
   */
  protected function assertMailSentNotContainingString(string $string, int $mailIndex = 0): void {
    $mail = $this->form->getMail()[$mailIndex];
    $this->assertStringNotContainsString(preg_replace('/\s+/', '', $string), preg_replace('/\s+/', '', $mail['body']));
  }

  /**
   * Assert that the sent mail included the supplied string.
   *
   * @param string $string
   * @param int $mailIndex
   */
  protected function assertMailSentContainingHeaderString(string $string, int $mailIndex = 0): void {
    $mail = $this->form->getMail()[$mailIndex];
    $this->assertStringContainsString($string, $mail['headers']);
  }

  /**
   * Assert that the sent mail included the supplied strings.
   *
   * @param array $strings
   * @param int $mailIndex
   */
  protected function assertMailSentContainingHeaderStrings(array $strings, int $mailIndex = 0): void {
    foreach ($strings as $string) {
      $this->assertMailSentContainingHeaderString($string, $mailIndex);
    }
  }

  /**
   * Assert the right number of mails were sent.
   *
   * @param int $count
   */
  protected function assertMailSentCount(int $count): void {
    $this->assertCount($count, $this->form->getMail());
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

  protected function assertTemplateVariable($name, $expected): void {
    $this->assertEquals($expected, $this->form->getTemplateVariable($name));
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

  protected function assertPrematureExit(): void {
    $this->assertInstanceOf(\CRM_Core_Exception_PrematureExitException::class, $this->form->getException());
  }

}
