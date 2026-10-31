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
        ';\.aff\.html$;', function (\phpQueryObject $doc) {
          (new StringVisitor())->visit($doc, fn($s) => _ts($s));
        }
      );
    $angular->add($changeSet);
  }

}
