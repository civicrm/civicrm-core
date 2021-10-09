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

trait LocalizationTrait {

  /**
   * @var string|null
   * @scope tokenContext
   */
  protected $locale;

  /**
   * @return string
   */
  public function getLocale(): ?string {
    return $this->locale;
  }

  /**
   * @param string|null $locale
   * @return $this
   */
  public function setLocale(?string $locale) {
    $this->locale = $locale;
    return $this;
  }

  protected function validateExtra_localization(&$errors) {
    $allLangs = \CRM_Core_I18n::languages();
    if ($this->locale !== NULL && !isset($allLangs[$this->locale])) {
      $errors[] = [
        'severity' => 'error',
        'fields' => ['locale'],
        'name' => 'badLocale',
        'message' => ts('The given locale is not valid (%1)', [json_encode($this->locale)]),
      ];
    }
  }

}
