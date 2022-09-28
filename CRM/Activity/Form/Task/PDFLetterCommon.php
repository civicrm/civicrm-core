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

use Civi\Token\TokenProcessor;

/**
 * This class provides the common functionality for creating PDF letter for
 * activities.
 *
 * @deprecated
 */
class CRM_Activity_Form_Task_PDFLetterCommon extends CRM_Core_Form_Task_PDFLetterCommon {

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public static function postProcess(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $activityIds = $form->_activityHolderIds;
    $formValues = $form->controller->exportValues($form->getName());
    $html_message = CRM_Core_Form_Task_PDFLetterCommon::processTemplate($formValues);

    // Do the rest in another function to make testing easier
    $form->createDocument($activityIds, $html_message, $formValues);

    $form->postProcessHook();

    CRM_Utils_System::civiExit(1);
  }

  /**
   * Produce the document from the activities
   * This uses the new token processor
   *
   * @param  array $activityIds  array of activity ids
   * @param  string $html_message message text with tokens
   * @param  array $formValues   formValues from the form
   *
   * @deprecated
   *
   * @return string
   */
  public static function createDocument($activityIds, $html_message, $formValues) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $tp = self::createTokenProcessor();
    $tp->addMessage('body_html', $html_message, 'text/html');

    foreach ($activityIds as $activityId) {
      $tp->addRow()->context('activityId', $activityId);
    }
    $tp->evaluate();

    return self::renderFromRows($tp->getRows(), 'body_html', $formValues);
  }

  /**
   * Create a token processor
   *
   * @deprecated
   *
   * @return \Civi\Token\TokenProcessor
   */
  public static function createTokenProcessor() {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    return new TokenProcessor(\Civi::dispatcher(), [
      'controller' => get_class(),
      'smarty' => FALSE,
      'schema' => ['activityId'],
    ]);
  }

}
