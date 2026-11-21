<?php

namespace Civi\Afform;

use Civi\Core\Attribute\CiviEventListener;
use Civi\Angular\ChangeSet;
use Civi\Core\Service\AutoService;

/**
 * @service civi.afform.translator
 */
class Translator extends AutoService {

  /**
   * When changing to multilingual, ensure we extract all translation source strings
   */
  #[CiviEventListener(event: 'civi.core.makeMultilingual')]
  public function makeMultilingual() {
    \Civi\Afform\Utils::initSourceTranslations();
  }

  /**
   * @param \Civi\Angular\Manager $angular
   */
  #[CiviEventListener(event: '&hook_civicrm_alterAngular')]
  public function translateAfform($angular) {

    /**
     * Find all the content that should be auto send to ts function
     * There are 3 kinds of translation :
     * - html tag content
     * - html attribute values
     * - json sub-attribute in defn html attribute
     */

    $changeSet = ChangeSet::create('translate')
      ->alterHtml(
        ';\.aff\.html$;', function (\phpQueryObject $doc, string $path) {
          $formName = \Civi::service('angular')->getModules()[basename($path, '.aff.html')]['_afform'] ?? NULL;
          $domain = self::getDomain($formName);
          (new StringVisitor())->visit($doc, fn($s) => _ts($s, ['domain' => $domain]));
        }
      );
    $angular->add($changeSet);
  }

  /**
   * Translation domains for the strings of an Afform: those of the extension
   * that provides it, falling back to core's.
   *
   * @param string|null $formName
   *   Ex: 'afformFooBar'
   * @return array|null
   *   Ex: ['org.example.foo', NULL], or NULL for a form no extension provides.
   */
  public static function getDomain(?string $formName): ?array {
    $paths = $formName ? (\Civi::service('afform_scanner')->findFilePaths()[$formName] ?? []) : [];
    $baseModule = \CRM_Utils_Array::first(array_filter(array_keys($paths)));
    return $baseModule ? [$baseModule, NULL] : NULL;
  }

}
