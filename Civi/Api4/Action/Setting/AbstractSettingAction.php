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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
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
   * Checks that really ought to be taken care of by Civi::settings
   *
   * @param int $domain
   * @return array
   * @throws \API_Exception
   */
  protected function validateSettings($domain) {
    $meta = \Civi\Core\SettingsMetadata::getMetadata([], $domain);
    $names = isset($this->values) ? array_keys($this->values) : $this->select;
    $invalid = array_diff($names, array_keys($meta));
    if ($invalid) {
      throw new \API_Exception("Unknown settings for domain $domain: " . implode(', ', $invalid));
    }
    if (isset($this->values)) {
      foreach ($this->values as $name => &$value) {
        \CRM_Core_BAO_Setting::validateSetting($value, $meta[$name]);
      }
    }
    return $meta;
  }

  protected function findDomains() {
    if ($this->domainId == 'all') {
      $this->domainId = Domain::get()->setCheckPermissions(FALSE)->addSelect('id')->execute()->column('id');
    }
    elseif ($this->domainId) {
      $this->domainId = (array) $this->domainId;
      $domains = Domain::get()->setCheckPermissions(FALSE)->addSelect('id')->execute()->column('id');
      $invalid = array_diff($this->domainId, $domains);
      if ($invalid) {
        throw new \API_Exception('Invalid domain id: ' . implode(', ', $invalid));
      }
    }
    else {
      $this->domainId = [\CRM_Core_Config::domainID()];
    }
  }

}
