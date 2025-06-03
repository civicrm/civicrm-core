<?php

namespace Civi\Afform;

use Civi\Angular\ChangeSet;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.afform.translator
 */
class Translator extends AutoService implements EventSubscriberInterface {

  /**
   *
   */
  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_alterAngular' => 'translateAfform',
    ];
  }

  /**
   * @param \Civi\Angular\Manager $angular
   */
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
          $form = [];
          (new StringVisitor())->visit($form, $doc, fn($s) => _ts($s));
        }
      );
    $angular->add($changeSet);
  }

}
