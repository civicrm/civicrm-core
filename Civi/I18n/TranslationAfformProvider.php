<?php

namespace Civi\I18n;

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 *
 * @service
 * @internal
 */
class TranslationAfformProvider extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.afform.get' => 'getTranslationAfforms',
    ];
  }

  /**
   * Generates afforms for each ECK entity type and sub-type.
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   * @throws \CRM_Core_Exception
   */
  public static function getTranslationAfforms($event): void {
    if (!\CRM_Core_I18n::isMultilingual()) {
      return;
    }

    $afforms =& $event->afforms;
    $getNames = $event->getNames;

    // Early return if this api call is fetching afforms by name and those names are not related to translation
    if (
      (isset($getNames['name']) && !str_contains(implode(' ', $getNames['name']), 'afsearchTranslation'))
      || (isset($getNames['directive_name']) && !str_contains(implode(' ', $getNames['directive_name']), 'afsearch-translation'))
    ) {
      return;
    }

    $languages = \CRM_Core_I18n::languages();
    $locales = \CRM_Core_I18n::getMultilingual();

    // if forcing translation source, we don't want to offer the translation to default locale
    $force_translation_source_locale = \Civi::settings()->get('force_translation_source_locale') ?? TRUE;
    if ($force_translation_source_locale) {
      $defaultLocale = \Civi::settings()->get('lcMessages');
      $locales = array_diff($locales, [$defaultLocale]);
    }
    foreach ($locales as $index => $langCode) {
      $name = 'afsearchTranslation' . $langCode;
      $afforms[$name] = [
        'name' => $name,
        'type' => 'search',
        'title' => ts('Translations to %1', [1 => $languages[$langCode]]),
        'description' => NULL,
        'placement' => [],
        'placement_filters' => [],
        'placement_weight' => NULL,
        'tags' => NULL,
        'icon' => 'fa-language',
        'server_route' => "civicrm/admin/translation-$langCode",
        'is_public' => FALSE,
        'permission' => [
          'translate CiviCRM',
        ],
        'permission_operator' => 'AND',
      ];
      if ($event->getLayout) {
        $afforms[$name]['layout'] = \CRM_Core_Smarty::singleton()->fetchWith('Civi/I18n/afsearchTranslations.tpl', [
          'langCode' => $langCode,
        ]);
      }
    }
  }

}
