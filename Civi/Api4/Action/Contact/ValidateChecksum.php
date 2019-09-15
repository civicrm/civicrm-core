<?php

namespace Civi\Api4\Action\Contact;

use Civi\Api4\Generic\Result;

/**
 * Generate a security checksum for anonymous access to CiviCRM.
 *
 * @method $this setContactId(int $cid) Set contact ID (required)
 * @method int getContactId() Get contact ID param
 * @method $this setChecksum(string $checksum) Set checksum param (required)
 * @method string getChecksum() Get checksum param
 */
class ValidateChecksum extends \Civi\Api4\Generic\AbstractAction {

  /**
   * ID of contact
   *
   * @var int
   * @required
   */
  protected $contactId;

  /**
   * Value of checksum
   *
   * @var string
   * @required
   */
  protected $checksum;

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $result[] = [
      'valid' => \CRM_Contact_BAO_Contact_Utils::validChecksum($this->contactId, $this->checksum),
    ];
  }

}
