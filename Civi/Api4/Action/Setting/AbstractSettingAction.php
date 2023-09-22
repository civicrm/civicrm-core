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

namespace Civi\Api4\Action\Setting;

use Civi\Api4\Domain;
use Civi\Api4\Generic\Result;

/**
 * Base class for setting actions.
 *
 * @method int getDomainId
 * @method $this setDomainId(int $domainId)
 */
abstract class AbstractSettingAction extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Domain id of setting. Leave NULL for default domain.
   *
   * @var int|string|array
   */
  protected $domainId;

  /**
   * Contact - if this is a contact-related setting.
   *
   * @var int
   */
  protected $contactId;

  public function _run(Result $result) {
    $this->findDomains();
    $meta = [];
    foreach ($this->domainId as $domain) {
      $meta[$domain] = $this->validateSettings($domain);
    }
    foreach ($this->domainId as $domain) {
      $settingsBag = $this->contactId ? \Civi::contactSettings($this->contactId, $domain) : \Civi::settings($domain);
      $this->processSettings($result, $settingsBag, $meta[$domain], $domain);
    }
  }

  /**
   * Checks that really ought to be taken care of by `Civi::settings`.
   *
   * @param int $domain
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function validateSettings($domain) {
    $meta = \Civi\Core\SettingsMetadata::getMetadata([], $domain);
    $names = array_map(function($name) {
      return explode(':', $name)[0];
    }, isset($this->values) ? array_keys($this->values) : $this->select);
    $invalid = array_diff($names, array_keys($meta));
    if ($invalid) {
      throw new \CRM_Core_Exception("Unknown settings for domain $domain: " . implode(', ', $invalid));
    }
    if (isset($this->values)) {
      foreach ($this->values as $name => $value) {
        [$name, $suffix] = array_pad(explode(':', $name), 2, NULL);
        // Replace pseudoconstants in values array
        if ($suffix) {
          $value = $this->matchPseudoconstant($name, $value, $suffix, 'id', $domain);
          unset($this->values["$name:$suffix"]);
          $this->values[$name] = $value;
        }
        \CRM_Core_BAO_Setting::validateSetting($this->values[$name], $meta[$name], FALSE);

      }
    }
    return $meta;
  }

  protected function findDomains() {
    if ($this->domainId == 'all') {
      $this->domainId = Domain::get(FALSE)->addSelect('id')->execute()->column('id');
    }
    elseif ($this->domainId) {
      $this->domainId = (array) $this->domainId;
      $domains = Domain::get(FALSE)->addSelect('id')->execute()->column('id');
      $invalid = array_diff($this->domainId, $domains);
      if ($invalid) {
        throw new \CRM_Core_Exception('Invalid domain id: ' . implode(', ', $invalid));
      }
    }
    else {
      $this->domainId = [\CRM_Core_Config::domainID()];
    }
  }

  /**
   * @param string $name
   * @param mixed $value
   * @param string $from
   * @param string $to
   * @param int $domain
   * @return mixed
   */
  protected function matchPseudoconstant(string $name, $value, $from, $to, $domain) {
    if ($value === NULL) {
      return NULL;
    }
    if ($from === $to) {
      return $value;
    }
    $meta = \Civi\Core\SettingsMetadata::getMetadata(['name' => [$name]], $domain, [$from, $to]);
    $options = $meta[$name]['options'] ?? [];
    $map = array_column($options, $to, $from);
    $translated = [];
    foreach ((array) $value as $key) {
      if (isset($map[$key])) {
        $translated[] = $map[$key];
      }
    }
    if (!is_array($value)) {
      return \CRM_Utils_Array::first($translated);
    }
    return $translated;
  }

}
