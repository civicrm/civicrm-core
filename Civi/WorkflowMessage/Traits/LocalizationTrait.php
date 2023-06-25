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
   * The language that was requested to be rendered.
   *
   * This may not be the rendered locale - as the requested language
   * might be available. This is primarily for extensions to use in
   * custom workflow messages.
   *
   * @var string|null
   * @scope tokenContext
   */
  protected $requestedLocale;

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

  /**
   * Get the requested locale.
   *
   * This may differ from the rendered locale (e.g. if a translation is not
   * available). It is not used in core but extensions may leverage this
   * information.
   *
   * @return string
   */
  public function getRequestedLocale(): ?string {
    return $this->locale;
  }

  /**
   * Set the requested locale.
   *
   * @param string|null $requestedLocale
   *
   * @return $this
   */
  public function setRequestedLocale(?string $requestedLocale): self {
    $this->requestedLocale = $requestedLocale;
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
