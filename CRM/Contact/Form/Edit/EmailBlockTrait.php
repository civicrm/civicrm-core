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

use Civi\API\EntityLookupTrait;
use Civi\Api4\Email;
use Civi\Api4\Generic\Result;

/**
 * Form helper trait for including emails in forms.
 *
 * @internal not supported for use outside core - if you do use it ensure your
 *  code has adequate unit test cover.
 */
trait CRM_Contact_Form_Edit_EmailBlockTrait {
  use CRM_Contact_Form_Edit_BlockCustomDataTrait;
  use EntityLookupTrait;

  /**
   * @var \Civi\Api4\Generic\Result
   */
  private Result $existingEmails;

  /**
   *
   * @param bool $nonContact
   *    If TRUE, this will NOT return emails belonging to the form-associated ContactID.
   *    This is used by the Event form which should not silently default to the email
   *    address(es) of the contact who created the Event.
   *    See https://lab.civicrm.org/dev/core/-/issues/6127
   * @return \Civi\Api4\Generic\Result
   * @throws CRM_Core_Exception
   */
  public function getExistingEmails($nonContact = FALSE) : Result {
    if (!isset($this->existingEmails)) {
      if ($this->getLocationBlockID()) {
        // In this scenario we are looking up the emails for an event or domain & so we do not apply permissions
        $this->existingEmails = Email::get(FALSE)
          ->addSelect('*', 'custom.*')
          ->addOrderBy('is_primary', 'DESC')
          ->addWhere('id', 'IN', [$this->getLocationBlockValue('email_id'), $this->getLocationBlockValue('email_2_id')])
          ->execute();
      }
      elseif (!$nonContact && $this->getContactID()) {
        $this->existingEmails = Email::get()
          ->addSelect('*', 'custom.*')
          ->addOrderBy('is_primary', 'DESC')
          ->addWhere('contact_id', '=', $this->getContactID())
          ->execute();
      }
    }
    return isset($this->existingEmails) ? $this->existingEmails : new Result();
  }

  /**
   * Return the value from civicrm_loc_block to get emails for.
   *
   * @return int|null
   */
  public function getLocationBlockID(): ?int {
    return NULL;
  }

  /**
   * Get a value from the location block associated with the event.
   *
   * @api supported for use from outside of core.
   *
   * @param string $value
   *
   * @return mixed|null
   */
  public function getLocationBlockValue(string $value) {
    if (!$this->getLocationBlockID()) {
      return NULL;
    }
    if (!$this->isDefined('LocationBlock')) {
      $this->define('LocBlock', 'LocationBlock', ['id' => $this->getLocationBlockID()]);
    }
    return $this->lookup('LocationBlock', $value);
  }

  /**
   * Get the open ids indexed numerically from 1.
   *
   * This reflects historical form requirements.
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getExistingEmailsReIndexed() : array {
    $result = array_merge([0 => 1], (array) $this->getExistingEmails());
    unset($result[0]);
    return $result;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function addEmailBlockFields(int $blockNumber): void {
    $this->addEmailBlockNonContactFields($blockNumber);
    $this->addEmailBlockContactFields($blockNumber);
  }

  /**
   * Add the email block fields that are not contact-specific.
   *
   * This is used, for example, by the Event Location form which is not interested in 'is_primary', 'is_bulkmail' etc.
   * @throws \CRM_Core_Exception
   */
  protected function addEmailBlockNonContactFields(int $blockNumber): void {
    $this->addField("email[$blockNumber][email]", [
      'entity' => 'email',
      'aria-label' => ts('Email %1', [1 => $blockNumber]),
      'label' => ts('Email %1', [1 => $blockNumber]),
    ]);
    $this->addRule("email[$blockNumber][email]", ts('Email is not valid.'), 'email');
    $this->addCustomDataFieldBlock('Email', $blockNumber);
  }

  /**
   * Add the email block fields that are contact-specific.
   *
   * @throws \CRM_Core_Exception
   */
  protected function addEmailBlockContactFields(int $blockNumber): void {
    //Block type
    $this->addField("email[$blockNumber][location_type_id]", ['entity' => 'email', 'placeholder' => NULL, 'class' => 'eight', 'option_url' => NULL]);

    //TODO: Refactor on_hold field to select.
    $multipleBulk = CRM_Core_BAO_Email::isMultipleBulkMail();

    //On-hold select
    if ($multipleBulk) {
      $holdOptions = [
        0 => ts('- select -'),
        1 => ts('On Hold Bounce'),
        2 => ts('On Hold Opt Out'),
      ];
      $this->addElement('select', "email[$blockNumber][on_hold]", '', $holdOptions);
    }
    else {
      $this->addField("email[$blockNumber][on_hold]", ['entity' => 'email', 'type' => 'advcheckbox', 'aria-label' => ts('On Hold for Email %1?', [1 => $blockNumber])]);
    }

    //Bulkmail checkbox
    $this->assign('multipleBulk', $multipleBulk);
    $js = [
      'id' => 'Email_' . $blockNumber . '_IsBulkmail',
      'aria-label' => ts('Bulk Mailing for Email %1?', [1 => $blockNumber]),
      'onChange' => "if (CRM.$(this).is(':checked')) {
          CRM.$('.crm-email-bulkmail input').not(this).prop('checked', false);
        }",
    ];

    $this->addElement('advcheckbox', "email[$blockNumber][is_bulkmail]", NULL, '', $js);

    //is_Primary radio
    $js = [
      'id' => 'Email_' . $blockNumber . '_IsPrimary',
      'aria-label' => ts('Email %1 is primary?', [1 => $blockNumber]),
      'class' => 'crm-email-is_primary',
      'onChange' => "if (CRM.$(this).is(':checked')) {
          CRM.$('.crm-email-is_primary').not(this).prop('checked', false);
        }",
    ];
    $this->addElement('radio', "email[$blockNumber][is_primary]", '', '', '1', $js);
  }

  /**
   * @throws UnauthorizedException
   * @throws CRM_Core_Exception
   */
  public function saveEmails(array $emails): void {
    $existingEmails = (array) $this->getExistingEmails()->indexBy('id');
    foreach ($emails as $index => $email) {
      $id = $email['id'] ?? NULL;
      $dataExists = !CRM_Utils_System::isNull($email['email']);
      if (!$dataExists) {
        unset($emails[$index]);
        continue;
      }
      if (!array_key_exists('contact_id', $email)) {
        $emails[$index]['contact_id'] = $this->getContactID();
      }
      if ($id) {
        if (array_key_exists($id, $existingEmails)) {
          // We unset this here because we are going to delete any existing
          // emails that were not in the incoming array.
          unset($existingEmails[$id]);
        }
        else {
          // The id is not valid, this becomes a create.
          unset($email['id']);
        }
      }
    }
    if ($emails) {
      Email::save()
        ->setRecords($emails)
        ->execute();
    }

    if (!empty($existingEmails)) {
      Email::delete()->addWhere('id', 'IN', array_keys($existingEmails))
        ->execute();
    }

  }

}
