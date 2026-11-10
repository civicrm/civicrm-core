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

namespace Civi\Api4\Action\Contact;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Generic\Result;

/**
 * Generate a security checksum for anonymous access to CiviCRM.
 *
 * @method $this setContactId(int $cid) Set contact ID (required)
 * @method int getContactId() Get contact ID param
 * @method $this setChecksum(string $checksum) Set checksum param (required)
 * @method string getChecksum() Get checksum param
 * @method $this setAsOf(?int $asOf) Set asOf param
 * @method int|null getAsOf() Get asOf param
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
   * Unix timestamp to check expiry against, e.g. when a queued request was made. Defaults to now.
   *
   * Only for internal calls and administrators because an earlier time accepts checksums that may have since expired.
   *
   * @var int
   */
  protected $asOf = NULL;

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    if ($this->asOf !== NULL && $this->getCheckPermissions() && !\CRM_Core_Permission::check('administer CiviCRM')) {
      throw new UnauthorizedException('Checking a checksum as of another time requires administer CiviCRM permission.');
    }
    $result[] = [
      'valid' => \CRM_Contact_BAO_Contact_Utils::validChecksum($this->contactId, $this->checksum, asOf: $this->asOf),
    ];
  }

}
