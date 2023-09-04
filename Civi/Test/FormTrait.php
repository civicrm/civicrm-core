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
   * @param $formName
   * @param $submittedValues
   * @param array $urlParameters
   *
   * @return \Civi\Test\FormWrapper
   */
  public function getTestForm($formName, $submittedValues, array $urlParameters = []) {
    if ($formName === 'CRM_Event_Form_Participant') {
      return new EventFormParticipant($formName, $submittedValues, $urlParameters);
    }
    if ($formName === 'CRM_Event_Form_Registration_Register') {
      return new EventFormOnline($formName, $submittedValues, $urlParameters);
    }
    return new FormWrapper($formName, $submittedValues, $urlParameters);
  }

}
