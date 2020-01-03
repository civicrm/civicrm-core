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

use Civi\Token\TokenProcessor;

/**
 * This class provides the common functionality for creating PDF letter for
 * activities.
 *
 */
class CRM_Activity_Form_Task_PDFLetterCommon extends CRM_Core_Form_Task_PDFLetterCommon {

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param CRM_Core_Form $form
   * @param $activityIds
   *
   * @return void
   */
  public static function postProcess(&$form) {
    $activityIds = $form->_activityHolderIds;
    $formValues = $form->controller->exportValues($form->getName());
    $html_message = self::processTemplate($formValues);

    // Do the rest in another function to make testing easier
    self::createDocument($activityIds, $html_message, $formValues);

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
   * @return void
   */
  public static function createDocument($activityIds, $html_message, $formValues) {
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
   */
  public static function createTokenProcessor() {
    return new TokenProcessor(\Civi::dispatcher(), array(
      'controller' => get_class(),
      'smarty' => FALSE,
      'schema' => ['activityId'],
    ));
  }

}
