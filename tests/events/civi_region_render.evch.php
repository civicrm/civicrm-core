<?php
return new class() extends \Civi\Test\EventCheck implements \Civi\Test\HookInterface {

  private $validSnippetTypes = [
    'callback',
    'jquery',
    'markup',
    'script',
    'scriptFile',
    'scriptUrl',
    'settings',
    'style',
    'styleFile',
    'styleUrl',
    'template',
  ];

  private $validRegion = '/^[A-Za-z0-9\\-]+$/';

  /**
   * Ensure that the hook data is always well-formed.
   */
  public function on_civi_region_render(\Civi\Core\Event\GenericHookEvent $e) {
    $this->assertTrue($e->region instanceof \CRM_Core_Region);
    /** @var \CRM_Core_Region $region */
    $region = $e->region;
    $this->assertRegexp($this->validRegion, $region->_name);
    foreach ($region->getAll() as $snippet) {
      $this->assertContains($snippet['type'], $this->validSnippetTypes);
    }
  }

};
