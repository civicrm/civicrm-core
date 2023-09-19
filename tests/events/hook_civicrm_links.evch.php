<?php

use Civi\Test\EventCheck;
use Civi\Test\HookInterface;

return new class() extends EventCheck implements HookInterface {

  /**
   * These are $objectNames that deviate from the normal "CamelCase" convention.
   * They're allowed for backward-compatibility.
   *
   * @var string[]
   */
  protected $grandfatheredObjectNames = [
    'CRM_Core_BAO_LocationType',
  ];

  /**
   * These are contexts where the "url" can be replaced with an onclick handler.
   * It evidently works on some screens, but it doesn't sound reliable.
   * They're allowed for backward-compatibility.
   *
   * @var string[]
   */
  protected $grandfatheredOnClickLinks = [
    'case.tab.row::Activity',
  ];

  /**
   * These variants have majorly deviant data in $links.
   * They are protected by pre-existing unit-tests.
   * They're allowed for backward-compatibility.
   *
   * @var string[]
   */
  protected $grandfatheredInvalidLinks = [
    'pcp.user.actions::Pcp',
  ];

  /**
   * Ensure that the hook data is always well-formed.
   *
   * @see \CRM_Utils_Hook::links()
   */
  public function hook_civicrm_links($op, $objectName, &$objectId, &$links, &$mask = NULL, &$values = []): void {
    // fprintf(STDERR, "CHECK hook_civicrm_links($op)\n");
    $msg = sprintf('Non-conforming hook_civicrm_links(%s, %s)', json_encode($op), json_encode($objectName));

    $this->assertTrue((bool) preg_match(';^\w+(\.\w+)+$;', $op), "$msg: Operation ($op) should be dotted expression");
    $this->assertTrue((bool) preg_match(';^[A-Z][a-zA-Z0-9]+$;', $objectName) || in_array($objectName, $this->grandfatheredObjectNames),
      "$msg: Object name ($objectName) should be a CamelCase name or a grandfathered name");

    // $this->assertType('integer|null', $objectId, "$msg: Object ID ($objectId) should be int|null");
    $this->assertTrue($objectId === NULL || is_numeric($objectId), "$msg: Object ID ($objectId) should be int|null");
    // Sometimes it's a string-style int. Patch-welcome if someone wants to clean that up. But this is what it currently does.

    $this->assertType('array', $links, "$msg: Links should be an array");
    $this->assertType('integer|null', $mask, "$msg: Mask ($mask) should be int}null");
    $this->assertType('array', $values, "$msg: Values should be an array");

    if (in_array("$op::$objectName", $this->grandfatheredInvalidLinks)) {
      return;
    }
    foreach ($links as $link) {
      if (isset($link['name'])) {
        $this->assertType('string', $link['name'], "$msg: name should be a string");
      }
      else {
        $this->fail("$msg: name is missing");
      }

      if (isset($link['url'])) {
        $this->assertType('string', $link['url'], "$msg: url should be a string");
      }
      elseif (in_array("$op::$objectName", $this->grandfatheredOnClickLinks)) {
        $this->assertTrue((bool) preg_match(';onclick;', $link['extra']), "$msg: ");
      }
      else {
        $this->fail("$msg: url is missing");
      }

      if (isset($link['qs'])) {
        $this->assertType('string|array', $link['qs'], "$msg: qs should be a string");
      }
      if (isset($link['title'])) {
        $this->assertType('string', $link['title'], "$msg: title should be a string");
      }
      if (isset($link['extra'])) {
        $this->assertType('string', $link['extra'], "$msg: extra should be a string");
      }
      if (isset($link['bit'])) {
        $this->assertType('integer', $link['bit'], "$msg: bit should be an int");
      }
      if (isset($link['ref'])) {
        $this->assertType('string', $link['ref'], "$msg: ref should be an string");
      }
      if (isset($link['class'])) {
        $this->assertType('string', $link['class'], "$msg: class should be a string");
      }
      $this->assertTrue(isset($link['weight']) && is_numeric($link['weight']), "$msg: weight should be numerical");
      if (isset($link['accessKey'])) {
        $this->assertTrue(is_string($link['accessKey']) && mb_strlen($link['accessKey']) <= 1, "$msg: accessKey should be a letter");
      }
      if (isset($link['icon'])) {
        $this->assertTrue((bool) preg_match(';^fa-[-a-z0-9]+$;', $link['icon']), "$msg: Icon ({$link['icon']}) should be FontAwesome icon class");
      }

      $expectKeys = ['name', 'url', 'qs', 'title', 'extra', 'bit', 'ref', 'class', 'weight', 'accessKey', 'icon'];
      $extraKeys = array_diff(array_keys($link), $expectKeys);
      $this->assertEquals([], $extraKeys, "$msg: Link has unrecognized keys: " . json_encode($extraKeys));
    }
  }

};
