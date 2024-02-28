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
namespace Civi\FlexMailer\Listener;

use Civi\Core\Service\AutoService;
use CRM_Flexmailer_ExtensionUtil as E;
use Civi\FlexMailer\Event\CheckSendableEvent;

/**
 * Class RequiredFields
 * @package Civi\FlexMailer\Listener
 *
 * The RequiredFields listener checks that all mandatory fields have a value.
 */
class RequiredFields extends AutoService {

  use IsActiveTrait;

  /**
   * @service civi_flexmailer_required_fields
   */
  public static function factory(): RequiredFields {
    return new static([
      'subject',
      'name',
      'from_name',
      'from_email',
      '(body_html|body_text)',
    ]);
  }

  /**
   * @var array
   *   Ex: array('subject', 'from_name', '(body_html|body_text)').
   */
  private $fields;

  /**
   * RequiredFields constructor.
   * @param array $fields
   */
  public function __construct($fields) {
    $this->fields = $fields;
  }

  /**
   * Check for required fields.
   *
   * @param \Civi\FlexMailer\Event\CheckSendableEvent $e
   */
  public function onCheckSendable(CheckSendableEvent $e) {
    if (!$this->isActive()) {
      return;
    }

    foreach ($this->fields as $field) {
      // Parentheses indicate multiple options. Ex: '(body_html|body_text)'
      if ($field[0] === '(') {
        $alternatives = explode('|', substr($field, 1, -1));
        $fieldTitle = implode(' or ', array_map(function ($x) {
          return "\"$x\"";
        }, $alternatives));
        $found = $this->hasAny($e->getMailing(), $alternatives);
      }
      else {
        $fieldTitle = "\"$field\"";
        $found = !empty($e->getMailing()->{$field});
      }

      if (!$found) {
        $e->setError($field, E::ts('Field %1 is required.', [
          1 => $fieldTitle,
        ]));
      }
      unset($found);
    }
  }

  /**
   * Determine if $object has any of the given properties.
   *
   * @param mixed $object
   * @param array $alternatives
   * @return bool
   */
  protected function hasAny($object, $alternatives) {
    foreach ($alternatives as $alternative) {
      if (!empty($object->{$alternative})) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get the list of required fields.
   *
   * @return array
   *   Ex: array('subject', 'from_name', '(body_html|body_text)').
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * Set the list of required fields.
   *
   * @param array $fields
   *   Ex: array('subject', 'from_name', '(body_html|body_text)').
   * @return RequiredFields
   */
  public function setFields($fields) {
    $this->fields = $fields;
    return $this;
  }

}
