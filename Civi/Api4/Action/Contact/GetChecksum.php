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

use Civi\Api4\Generic\Result;

/**
 * Generate a security checksum for anonymous access to CiviCRM.
 *
 * @method $this setContactId(int $cid) Set contact ID (required)
 * @method int getContactId() Get contact ID param
 * @method $this setTtl(int $ttl) Set TTL param
 * @method int getTtl() Get TTL param
 */
class GetChecksum extends \Civi\Api4\Generic\AbstractAction {

  /**
   * ID of contact
   *
   * @var int
   * @required
   */
  protected $contactId;

  /**
   * Expiration time (hours). Defaults to 168 (24 * [7 or value of checksum_timeout system setting]).
   *
   * Set to 0 for infinite.
   *
   * @var int
   */
  protected $ttl = NULL;

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $ttl = ($this->ttl === 0 || $this->ttl === '0') ? 'inf' : $this->ttl;
    $result[] = [
      'id' => $this->contactId,
      'checksum' => \CRM_Contact_BAO_Contact_Utils::generateChecksum($this->contactId, NULL, $ttl),
    ];
  }

}
