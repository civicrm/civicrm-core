<?php

namespace Civi\AfformMock;

use Civi\Test\HttpTestTrait;

/**
 * Class FormTestCase
 *
 * This is a particular style of end-to-end test in which:
 *
 * - You make a concrete file with the target form (ang/FOO.aff.html)
 * - You make HTTP requests for that form (FOO).
 *
 * This style of test is useful if:
 *
 * - You want to test a specific/concrete form, and
 * - You want to test runtime behavior, and
 * - You want to be able to run the form manually (for closer inspection).
 *
 * Note that this implies some ownership over the life of the form -- e.g. to
 * ensure consistent execution, it will revert any local overrides on the target form.
 *
 * @package Civi\AfformMock
 */
abstract class FormTestCase extends \PHPUnit\Framework\TestCase implements \Civi\Test\EndToEndInterface {

  use HttpTestTrait;

  protected $formName = NULL;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.afform-mock'])
      ->apply();
  }

  protected function setUp(): void {
    parent::setUp();

    if ($this->formName === NULL && preg_match(';^(.*)\.test\.php$;', basename(static::FILE), $m)) {
      $this->formName = $m[1];
    }

    $meta = $this->getFormMeta();
    if (!$meta['has_base']) {
      $this->fail('Cannot run test because form lacks a baseline definition.');
    }
    if ($meta['has_local']) {
      $this->revertForm();
    }
  }

  protected function tearDown(): void {
    parent::tearDown();
  }

  protected function revertForm() {
    \Civi\Api4\Afform::revert(FALSE)
      ->addWhere('name', '=', $this->getFormName())
      ->execute();
    return $this;
  }

  /**
   * Get the name of the target form.
   *
   * @return string
   */
  protected function getFormName() {
    if ($this->formName === NULL) {
      throw new \RuntimeException("Failed to determine form name for " . self::FILE . ". Please override \$formName or getFormName().");
    }
    return $this->formName;
  }

  /**
   * Get the metadata for the target form.
   *
   * @return array
   */
  protected function getFormMeta(): array {
    $scanner = new \CRM_Afform_AfformScanner();
    $meta = $scanner->getMeta($this->getFormName());
    if (empty($meta)) {
      throw new \RuntimeException(sprintf("Failed to find metadata for form (%s)", $this->getFormName()));
    }
    $scanner->addComputedFields($meta);
    return $meta;
  }

  /**
   * Call the 'Afform.prefill' for this form.
   *
   * @param array $params
   *
   * @return mixed
   */
  protected function prefill($params) {
    $params['name'] ??= $this->getFormName();
    return $this->callApi4AjaxSuccess('Afform', 'prefill', $params);
  }

  /**
   * Call the 'Afform.prefill' for this form.
   *
   * @param array $params
   *
   * @return mixed
   */
  protected function prefillError($params) {
    $params['name'] ??= $this->getFormName();
    return $this->callApi4AjaxError('Afform', 'prefill', $params);
  }

  /**
   * Call the 'Afform.submit' for this form.
   *
   * @param array $params
   *
   * @return mixed
   */
  protected function submit($params) {
    $params['name'] ??= $this->getFormName();
    return $this->callApi4AjaxSuccess('Afform', 'submit', $params);
  }

  /**
   * Call the 'Afform.submit' for this form.
   *
   * @param array $params
   *
   * @return mixed
   */
  protected function submitError($params) {
    $params['name'] ??= $this->getFormName();
    return $this->callApi4AjaxError('Afform', 'submit', $params);
  }

}
