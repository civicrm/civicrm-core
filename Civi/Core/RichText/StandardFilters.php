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

namespace Civi\Core\RichText;

use Civi;

/**
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @service richtext.standard_filters
 */
class StandardFilters extends Civi\Core\Service\AutoService {

  private ?\Phlib\XssSanitizer\Sanitizer $xssSanitizer;

  private ?\HTMLPurifier $htmlPurifier;

  public function uri(string $content, array $format): string {
    // TODO: check $format['uri_schemes']. Search $content for images, links, CSS-URLs, etc
    return $content;
  }

  public function xss(string $content, array $format): string {
    $this->xssSanitizer ??= new \Phlib\XssSanitizer\Sanitizer();
    return $this->xssSanitizer->sanitize($content);
  }

  public function htmlPurifier(string $content, array $format): string {
    $this->htmlPurifier ??= $this->createPurifier();
    return $this->htmlPurifier->purify($content);
  }

  protected function createPurifier(): \HTMLPurifier {
    $config = \HTMLPurifier_Config::createDefault();
    $config->set('Core.Encoding', 'UTF-8');
    $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);
    // Disable the cache entirely
    $config->set('Cache.DefinitionImpl', NULL);
    $config->set('HTML.DefinitionID', 'enduser-customize.html tutorial');
    $config->set('HTML.DefinitionRev', 1);
    $config->set('HTML.MaxImgLength', NULL);
    $config->set('CSS.MaxImgLength', NULL);
    // Prevent id atrributes from being stripped (useful for e.g. anchors)
    $config->set('Attr.EnableID', TRUE);
    $config->set('URI.AllowedSymbols', '!$&\'()*+,;={}');
    $def = $config->maybeGetRawHTMLDefinition();
    $uri = $config->getDefinition('URI');
    $uri->addFilter(new \CRM_Utils_HTMLPurifier_URIFilter(), $config);

    if (!empty($def)) {
      $def->addElement('figcaption', 'Block', 'Flow', 'Common');
      $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
      // Allow `<summary>` and `<details>`
      $def->addElement('details', 'Block', 'Flow', 'Common', [
        'open' => new \HTMLPurifier_AttrDef_HTML_Bool('open'),
      ]);
      $def->addElement('summary', 'Inline', 'Inline', 'Common');
    }
    return new \HTMLPurifier($config);
  }

}
