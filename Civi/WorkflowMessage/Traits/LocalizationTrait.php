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
   * The use-case is a bit like this:
   *
   * 1. Your organization serves many similar locales (eg es_MX+es_CR+es_HN or en_GB+en_US+en_NZ).
   * 2. You want to write one message (es_MX) for several locales (es_MX+es_CR+es_HN)
   * 3. You want to include some conditional content that adapts the recipient's requested locale
   *    (es_CR) -- _even though_ the template was stored as es_MX. For example your front end
   *    website has more nuanced translations than your workflow messages and you wish
   *    to redirect the user to a page on your website.
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
