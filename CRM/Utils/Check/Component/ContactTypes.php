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
class CRM_Utils_Check_Component_ContactTypes extends CRM_Utils_Check_Component {

  /**
   * TODO: This check should be removed when the contact_type.image_URL column is dropped
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkContactTypeIcons() {
    if (CRM_Utils_System::version() !== CRM_Core_BAO_Domain::version()) {
      return [];
    }

    $messages = [];
    $contactTypesWithImages = \Civi\Api4\ContactType::get(FALSE)
      ->addWhere('image_URL', 'IS NOT EMPTY')
      ->addWhere('icon', 'IS EMPTY')
      ->execute();

    if ($contactTypesWithImages->count()) {
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Please select an icon for the following contact types using the new icon picker, as image urls will not be supported in future versions of CiviCRM.'),
        ts('Contact type images are deprecated'),
        \Psr\Log\LogLevel::WARNING,
        'fa-picture-o'
      );
      foreach ($contactTypesWithImages as $contactType) {
        $message->addAction($contactType['label'], FALSE, 'href', ['path' => 'civicrm/admin/options/subtype/edit', 'query' => ['action' => 'update', 'id' => $contactType['id'], 'reset' => 1]], 'fa-pencil');
      }
      $messages[] = $message;
    }

    return $messages;
  }

}
