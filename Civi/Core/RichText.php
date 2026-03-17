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

namespace Civi\Core;

use Civi;
use Civi\Core\Event\GenericHookEvent;

/**
 * Helper to identify RichText formats and apply relevant filters.
 * These formats are generally dialects of HTML, and they may differ in the
 * specific tags/features which are required/allowed/prohibited.
 *
 * # Example: Call the filter for PHPWord-HTML
 * Civi::service('richtext')->filter('html2doc', $body_html);
 *
 * # Example: Change the list of formats
 * Civi::dispatcher()->addListener('&civi.richtext.formats', function(array &$formats) {
 *   $formats['html2doc']['filters'] = ['callable_expression'];
 * });
 *
 * Each item in 'filters' is a resolvable callback with the signature:
 *   `function(string $content, array $format): string`.
 *
 * @see \Civi\Core\Resolver
 *
 * In general, you should aim for each format to have 0-2 filters.
 * Why not more? Because the contract favors simplicity rather than scalability.
 * It allows us to swap libraries (e.g. swapping HTMLPurifier <=> XssSanitizer),
 * but it doesn't optimize parsing-costs or memory-costs.
 *
 * Theoretically, you -could- put in a long list of daisy-chained filters.
 * But then you'd likely have redundant parsing, extra memory-usage, etc.
 * You can come up with a better pipeline convention and bridge it, e.g.
 *
 * $formats['phpword']['filters'] = ['snazzy_pipeline'];
 * $formats['phpword']['snazzy'] = [...list of optimized filters from snazzy framework...];
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @service richtext
 */
class RichText extends \Civi\Core\Service\AutoService {

  private ?array $formats = NULL;

  private array $filterCallbacks = [];

  /**
   * Filter a list of strings, ensuring conformance with a specific format.
   *
   * @param string $format
   * @param string[] $values
   *   List of several strings.
   * @return array
   *   List of several strings, each one filtered.
   * @throws \CRM_Core_Exception
   */
  public function filterAll(string $format, array $values): array {
    $options = $this->getFormat($format);
    $filter = ($this->filterCallbacks[$format] ??= $this->createFilterCallback($options));
    $result = [];
    foreach ($values as $key => $value) {
      $result[$key] = $value === NULL ? $value : $filter($value, $options);
    }
    return $result;
  }

  /**
   * Filter a string, ensuring conformance with a specific format.
   *
   * @param string $format
   * @param string|null $value
   * @return string|null
   *   Modified content.
   */
  public function filter(string $format, ?string $value): ?string {
    $options = $this->getFormat($format);
    $filter = ($this->filterCallbacks[$format] ??= $this->createFilterCallback($options));
    return $value === NULL ? $value : $filter($value, $options);
  }

  protected function createFilterCallback(array $format) {
    $count = count($format['filters'] ?? []);
    if ($count === 0) {
      return fn($x) => $x;
    }
    elseif ($count === 1) {
      return Resolver::singleton()->get(reset($format['filters']));
    }
    else {
      $callbacks = array_map([Resolver::singleton(), 'get'], $format['filters']);
      return function (string $content, array $format) use ($callbacks) {
        foreach ($callbacks as $callback) {
          $content = $callback($content, $format);
        }
        return $content;
      };
    }
  }

  /**
   * Get the definition of a specific rich-text format.
   *
   * @param string $name
   * @return array|null
   */
  public function getFormat(string $name): array {
    $this->formats ??= $this->loadFormats();
    if (!isset($this->formats[$name])) {
      throw new \CRM_Core_Exception("Unknown rich-text format '{$name}'");
    }
    return $this->formats[$name];
  }

  /**
   * Get list of all available rich-text formats.
   *
   * @return array
   */
  public function getFormats(): array {
    $this->formats ??= $this->loadFormats();
    return $this->formats;
  }

  protected function loadFormats(): array {
    $formats = \CRM_Utils_Array::rekey($this->createDefaultFormats(), 'name');
    $event = GenericHookEvent::create(['formats' => &$formats]);
    Civi::dispatcher()->dispatch('civi.richtext.formats', $event);
    return $formats;
  }

  protected function createDefaultFormats(): array {
    return [
      [
        'name' => 'string',
        'label' => ts('Generic String'),
        'filters' => ['call://richtext.standard_filters/htmlPurifier'],
      ],
      [
        'name' => 'mailing',
        'label' => ts('Email Content'),
        'filters' => ['call://richtext.standard_filters/xss'],
      ],
      [
        'name' => 'html2doc',
        'label' => ts('Print HTML (%1)', [1 => 'PHPWord']),
        'filters' => ['call://richtext.standard_filters/uri'],
        'uri_schemes' => ['http', 'https', 'data'],
      ],
      [
        'name' => 'html2pdf_dompdf',
        'label' => ts('Print HTML (%1)', [1 => 'DOMPDF']),
        'filters' => ['call://richtext.standard_filters/uri'],
        // DOMPDF doesn't execute scripts, and it has its own URI filtering.
      ],
      [
        'name' => 'html2pdf_weasyprint',
        'label' => ts('Print HTML (%1)', [1 => 'weasyprint']),
        'filters' => ['call://richtext.standard_filters/xss', 'call://richtext.standard_filters/uri'],
        'uri_schemes' => ['http', 'https', 'data'],
      ],
      [
        'name' => 'html2pdf_wkhtmltopdf',
        'label' => ts('Print HTML (%1)', [1 => 'wkhtmltopdf']),
        'filters' => ['call://richtext.standard_filters/xss', 'call://richtext.standard_filters/uri'],
        'uri_schemes' => ['http', 'https', 'data'],
      ],
      [
        'name' => 'docx',
        'label' => ts('Print Document (%1)', [1 => 'docx']),
        'filters' => [],
        // Maybe there should be some filters for docx=>docx templating,
        // but it's not clear what they should be, and (in current workflows)
        // you cannot leverage this against another user -- only against yourself.
      ],
      [
        'name' => 'odt',
        'label' => ts('Print Document (%1)', [1 => 'odt']),
        'filters' => [],
        // Maybe there should be some filters for odt=>odt templating,
        // but it's not clear what they should be, and (in current workflows)
        // you cannot leverage this against another user -- only against yourself.
      ],
    ];
  }

}
