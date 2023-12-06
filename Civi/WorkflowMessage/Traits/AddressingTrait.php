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

namespace Civi\WorkflowMessage\Traits;

const ADDRESS_STORAGE_FMT = 'rfc822';
const ADDRESS_EXPORT_FMT = 'rfc822';

/**
 * Define the $to, $from, $replyTo, $cc, and $bcc fields to a WorkflowMessage class.
 *
 * Email addresses may be get or set in any of these formats:
 *
 * - rfc822 (string): RFC822-style, e.g. 'Full Name <user@example.com>'
 * - record (array): Pair of name+email, e.g. ['name' => 'Full Name', 'email' => 'user@example.com']
 * - records: (array) List of records, keyed sequentially.
 */
trait AddressingTrait {

  /**
   * The primary email recipient (single address).
   *
   * @var string|null
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => "Foo Bar", "email" => "foo.bar@example.com"]
   *
   * The "To:" address is mapped to the "envelope" scope. The existing
   * envelope format treats this as a pair of fields [toName,toEmail].
   * Consequently, we only support one "To:" address, and it uses a
   * special import/export method.
   */
  protected $to;

  /**
   * The email sender (single address).
   *
   * @var string|null
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   * @scope envelope
   */
  protected $from;

  /**
   * The email sender's Reply-To (single address).
   *
   * @var string|null
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   * @scope envelope
   */
  protected $replyTo;

  /**
   * Additional recipients (multiple addresses).
   *
   * @var string|null
   *   Ex: '"Foo Bar" <foo.bar@example.com>, "Whiz Bang" <whiz.bang@example.com>'
   *   Ex: [['name' => 'Foo Bar', 'email' => 'foo.bar@example.com'], ['name' => 'Whiz Bang', 'email' => 'whiz.bang@example.com']]
   * @scope envelope
   */
  protected $cc;

  /**
   * Additional recipients (multiple addresses).
   *
   * @var string|null
   *   Ex: '"Foo Bar" <foo.bar@example.com>, "Whiz Bang" <whiz.bang@example.com>'
   *   Ex: [['name' => 'Foo Bar', 'email' => 'foo.bar@example.com'], ['name' => 'Whiz Bang', 'email' => 'whiz.bang@example.com']]
   * @scope envelope
   */
  protected $bcc;

  /**
   * Get the list of "To:" addresses.
   *
   * Note: This returns only
   *
   * @param string $format
   *   Ex: 'rfc822', 'records', 'record'
   * @return array|string
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => 'Foo Bar', 'email' => 'foo.bar@example.com']
   */
  public function getTo($format = ADDRESS_EXPORT_FMT) {
    return $this->formatAddress($format, $this->to);
  }

  /**
   * Get the "From:" address.
   *
   * @param string $format
   *   Ex: 'rfc822', 'records', 'record'
   * @return array|string
   *   The "From" address. If none set, this will be empty ([]).
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => 'Foo Bar', 'email' => 'foo.bar@example.com']
   */
  public function getFrom($format = ADDRESS_EXPORT_FMT) {
    return $this->formatAddress($format, $this->from);
  }

  /**
   * Get the "Reply-To:" address.
   *
   * @param string $format
   *   Ex: 'rfc822', 'records', 'record'
   * @return array|string
   *   The "From" address. If none set, this will be empty ([]).
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => 'Foo Bar', 'email' => 'foo.bar@example.com']
   */
  public function getReplyTo($format = ADDRESS_EXPORT_FMT) {
    return $this->formatAddress($format, $this->replyTo);
  }

  /**
   * Get the list of "Cc:" addresses.
   *
   * @param string $format
   *   Ex: 'rfc822', 'records', 'record'
   * @return array|string
   *   List of addresses.
   *   Ex: 'First <first@example.com>, second@example.com'
   *   Ex: [['name' => 'First', 'email' => 'first@example.com'], ['email' => 'second@example.com']]
   */
  public function getCc($format = ADDRESS_EXPORT_FMT) {
    return $this->formatAddress($format, $this->cc);
  }

  /**
   * Get the list of "Bcc:" addresses.
   *
   * @param string $format
   *   Ex: 'rfc822', 'records', 'record'
   * @return array|string
   *   List of addresses.
   *   Ex: 'First <first@example.com>, second@example.com'
   *   Ex: [['name' => 'First', 'email' => 'first@example.com'], ['email' => 'second@example.com']]
   */
  public function getBcc($format = ADDRESS_EXPORT_FMT) {
    return $this->formatAddress($format, $this->bcc);
  }

  /**
   * @param string|array $address
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => "Foo Bar", "email" => "foo.bar@example.com"]
   * @return $this
   */
  public function setFrom($address) {
    $this->from = $this->formatAddress(ADDRESS_STORAGE_FMT, $address);
    return $this;
  }

  /**
   * @param string|array $address
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => "Foo Bar", "email" => "foo.bar@example.com"]
   * @return $this
   */
  public function setTo($address) {
    $this->to = $this->formatAddress(ADDRESS_STORAGE_FMT, $address);
    return $this;
  }

  /**
   * @param string|array $address
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => "Foo Bar", "email" => "foo.bar@example.com"]
   * @return $this
   */
  public function setReplyTo($address) {
    $this->replyTo = $this->formatAddress(ADDRESS_STORAGE_FMT, $address);
    return $this;
  }

  /**
   * Set the "CC:" list.
   *
   * @param string|array $address
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => "Foo Bar", "email" => "foo.bar@example.com"]
   *   Ex: [['email' => 'first@example.com'], ['email' => 'second@example.com']]
   * @return $this
   */
  public function setCc($address) {
    $this->cc = $this->formatAddress(ADDRESS_STORAGE_FMT, $address);
    return $this;
  }

  /**
   * Set the "BCC:" list.
   *
   * @param string|array $address
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => "Foo Bar", "email" => "foo.bar@example.com"]
   *   Ex: [['email' => 'first@example.com'], ['email' => 'second@example.com']]
   * @return $this
   */
  public function setBcc($address) {
    $this->bcc = $this->formatAddress(ADDRESS_STORAGE_FMT, $address);
    return $this;
  }

  /**
   * Add another "CC:" address.
   *
   * @param string|array $address
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => "Foo Bar", "email" => "foo.bar@example.com"]
   *   Ex: [['email' => 'first@example.com'], ['email' => 'second@example.com']]
   * @return $this
   */
  public function addCc($address) {
    return $this->setCc(array_merge(
      $this->getCc('records'),
      $this->formatAddress('records', $address)
    ));
  }

  /**
   * Add another "BCC:" address.
   *
   * @param string|array $address
   *   Ex: '"Foo Bar" <foo.bar@example.com>'
   *   Ex: ['name' => "Foo Bar", "email" => "foo.bar@example.com"]
   *   Ex: [['email' => 'first@example.com'], ['email' => 'second@example.com']]
   * @return $this
   */
  public function addBcc($address) {
    return $this->setBcc(array_merge(
      $this->getBcc('records'),
      $this->formatAddress('records', $address)
    ));
  }

  /**
   * Plugin to `WorkflowMessageInterface::import()` and handle toEmail/toName.
   *
   * @param array $values
   * @see \Civi\WorkflowMessage\Traits\ReflectiveWorkflowTrait::import
   */
  protected function importExtraEnvelope_toAddress(array &$values): void {
    if (isset($values['toEmail']) || isset($values['toName'])) {
      $this->setTo(['name' => $values['toName'] ?? NULL, 'email' => $values['toEmail'] ?? NULL]);
      unset($values['toName']);
      unset($values['toEmail']);
    }
  }

  /**
   * Plugin to `WorkflowMessageInterface::export()` and handle toEmail/toName.
   *
   * @param array $values
   * @see \Civi\WorkflowMessage\Traits\ReflectiveWorkflowTrait::export
   */
  protected function exportExtraEnvelope_toAddress(array &$values): void {
    $addr = $this->getTo('record');
    $values['toName'] = $addr['name'] ?? NULL;
    $values['toEmail'] = $addr['email'] ?? NULL;
  }

  /**
   * Convert an address to the desired format.
   *
   * @param string $newFormat
   *   Ex: 'rfc822', 'records', 'record'
   * @param array|string $mixed
   * @return array|string|null
   */
  private function formatAddress($newFormat, $mixed) {
    if ($mixed === NULL) {
      return NULL;
    }

    $oldFormat = is_string($mixed) ? 'rfc822' : (array_key_exists('email', $mixed) ? 'record' : 'records');
    if ($oldFormat === $newFormat) {
      return $mixed;
    }

    $recordToObj = function (?array $record) {
      return new \ezcMailAddress($record['email'], $record['name'] ?? '');
    };
    $objToRecord = function (?\ezcMailAddress $addr) {
      return is_null($addr) ? NULL : ['email' => $addr->email, 'name' => $addr->name];
    };

    // Convert $mixed to intermediate format (ezcMailAddress[] $objects) and then to final format.

    /** @var \ezcMailAddress[] $objects */

    switch ($oldFormat) {
      case 'rfc822':
        $objects = \ezcMailTools::parseEmailAddresses($mixed);
        break;

      case 'record':
        $objects = [$recordToObj($mixed)];
        break;

      case 'records':
        $objects = array_map($recordToObj, $mixed);
        break;

      default:
        throw new \RuntimeException("Unrecognized source format: $oldFormat");
    }

    switch ($newFormat) {
      case 'rfc822':
        // We use `implode(map(composeEmailAddress))` instead of `composeEmailAddresses` because the latter has header-line-wrapping.
        return implode(', ', array_map(['ezcMailTools', 'composeEmailAddress'], $objects));

      case 'record':
        if (count($objects) > 1) {
          throw new \RuntimeException("Cannot convert email addresses to record format. Too many addresses.");
        }
        return $objToRecord($objects[0] ?? NULL);

      case 'records':
        return array_map($objToRecord, $objects);

      default:
        throw new \RuntimeException("Unrecognized output format: $newFormat");
    }
  }

}
