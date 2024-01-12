<?php

use Civi\Test\EventCheck;
use Civi\Test\HookInterface;

return new class() extends EventCheck implements HookInterface {

  // There are several properties named "$grandfatherdXyz". These mark items which
  // pass through hook_civicrm_links but deviate from the plain interpretation of the docs.
  // Auditing or cleaning each would be its own separate project. Please feel free to
  // do that audit and figure how to normalize it. But for now, the goal of this file is
  // to limit the chaos - and prevent new/un-documented deviations.

  /**
   * These are $objectNames that deviate from the normal "CamelCase" convention.
   * They're allowed for backward-compatibility.
   *
   * @var string[]
   */
  protected $grandfatheredObjectNames = [
    'CRM_Core_BAO_LocationType',
    'CRM_Core_BAO_MessageTemplate',
    'CRM_Report_Form_*',
  ];

  /**
   * These are contexts where the "url" can be replaced by... magic?
   *
   * @var string[]
   */
  protected $grandfatheredNoUrl = [
    'basic.CRM_Core_BAO_LocationType.page::CRM_Core_BAO_LocationType',
    'case.tab.row::Activity',
    'financialItem.batch.row::FinancialItem',
    'group.selector.row::Group',
    'job.manage.action::Job',
    'membershipType.manage.action::MembershipType',
    'messageTemplate.manage.action::MessageTemplate',
    'basic.CRM_Core_BAO_MessageTemplate.page::CRM_Core_BAO_MessageTemplate',
  ];

  /**
   * These are deviant values with appear as `$link['bit']` fields. They are documented
   * (and generally practiced) as integers.
   *
   * @var \string[][]
   */
  protected $grandfatheredInvalidBits = [
    'financialItem.batch.row' => ['view', 'assign'],
  ];

  /**
   * These variants have anomalous keys that are not documented and do not
   * appear in most flavors of "hook_civicrm_links".
   *
   * @var \string[][]
   */
  protected $grandfatheredInvalidKeys = [
    'pledge.selector.row' => ['is_active'],
    'view.report.links' => ['confirm_message'],
    'contribution.selector.row' => ['result', 'key', 'is_single_mode', 'title_single_mode', 'filters'],
    'create.new.shortcuts' => ['shortCuts'],
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

  protected $grandfatheredInvalidWeights = [
    'create.new.shortcuts', /* These weights don't match the EDIT/VIEW/DELETE constructs... */
  ];

  /**
   * @var string[]
   */
  protected $grandfatheredInvalidObjectNames = [
    'create.new.shortcuts', /* Documented as passing NULL */
  ];

  /**
   * The specification says that `name` property stores the printable label.
   * But in some lists, this has been changed to a different property.
   * @var string[]
   */
  protected $grandfatheredNameFields = [
    'view.report.links' => 'label',
    'create.new.shortcuts' => 'title',
  ];

  /**
   * The $objectId is usually numeric. Some links are based on non-numeric IDs.
   *
   * @var array
   */
  protected $objectIdTypes = [
    'extension.local.action' => 'string',
    'extension.remote.action' => 'string',
  ];

  /**
   * List of events with multiple problems. These are completely ignored.
   *
   * @var string[]
   */
  protected $unrepentantMiscreants = [
    'create.new.shorcuts', /* Deprecated */
  ];

  /**
   * Ensure that the hook data is always well-formed.
   *
   * @see \CRM_Utils_Hook::links()
   */
  public function hook_civicrm_links($op, $objectName, &$objectId, &$links, &$mask = NULL, &$values = []): void {
    // fprintf(STDERR, "CHECK hook_civicrm_links($op)\n");
    $msg = sprintf('Non-conforming hook_civicrm_links(%s, %s)', json_encode($op), json_encode($objectName));

    if (in_array($op, $this->unrepentantMiscreants)) {
      return;
    }

    if (!in_array($op, $this->grandfatheredInvalidObjectNames)) {
      $matchGrandfatheredObjectNames = CRM_Utils_String::filterByWildcards($this->grandfatheredObjectNames, [$objectName]);
      $this->assertTrue((bool) preg_match(';^\w+(\.\w+)+$;', $op), "$msg: Operation ($op) should be dotted expression");
      $this->assertTrue((bool) preg_match(';^[A-Z][a-zA-Z0-9]+$;', $objectName) || !empty($matchGrandfatheredObjectNames),
        "$msg: Object name ($objectName) should be a CamelCase name or a grandfathered name");
    }

    if (isset($this->objectIdTypes[$op])) {
      $this->assertType($this->objectIdTypes[$op], $objectId, "$msg: Object ID ($objectId) should be " . $this->objectIdTypes[$op]);
    }
    else {
      // $this->assertType('integer|null', $objectId, "$msg: Object ID ($objectId) should be int|null");
      $this->assertTrue($objectId === NULL || is_numeric($objectId), "$msg: Object ID ($objectId) should be int|null");
      // Sometimes it's a string-style int. Patch-welcome if someone wants to clean that up. But this is what it currently does.
    }

    $this->assertType('array', $links, "$msg: Links should be an array");
    $this->assertType('integer|null', $mask, "$msg: Mask ($mask) should be int}null");
    $this->assertType('array', $values, "$msg: Values should be an array");

    if (in_array("$op::$objectName", $this->grandfatheredInvalidLinks)) {
      return;
    }
    $nameField = $this->grandfatheredNameFields[$op] ?? 'name';
    foreach ($links as $link) {
      if (isset($link[$nameField])) {
        $this->assertType('string', $link[$nameField], "$msg: $nameField should be a string");
      }
      else {
        $this->fail("$msg: name is missing");
      }

      if (isset($link['url'])) {
        $this->assertType('string', $link['url'], "$msg: url should be a string");
      }
      elseif (in_array("$op::$objectName", $this->grandfatheredNoUrl)) {
        // This context is allowed to have links without urls. God knows why.
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
        if (in_array($link['bit'], $this->grandfatheredInvalidBits[$op] ?? [])) {
          // Exception
        }
        else {
          $this->assertType('integer', $link['bit'], "$msg: bit should be an int" . $link['bit']);
        }
      }
      if (isset($link['ref'])) {
        $this->assertType('string', $link['ref'], "$msg: ref should be an string");
      }
      if (isset($link['class'])) {
        $this->assertType('string', $link['class'], "$msg: class should be a string");
      }
      if (!in_array($op, $this->grandfatheredInvalidWeights)) {
        $this->assertTrue(isset($link['weight']) && is_numeric($link['weight']), "$msg: weight should be numerical");
      }
      if (isset($link['accessKey'])) {
        $this->assertTrue(is_string($link['accessKey']) && mb_strlen($link['accessKey']) <= 1, "$msg: accessKey should be a letter");
      }
      if (isset($link['icon'])) {
        $this->assertTrue((bool) preg_match(';^fa-[-a-z0-9]+$;', $link['icon']), "$msg: Icon ({$link['icon']}) should be FontAwesome icon class");
      }

      $expectKeys = array_merge(
        [$nameField, 'url', 'qs', 'title', 'extra', 'bit', 'ref', 'class', 'weight', 'accessKey', 'icon'],
        $this->grandfatheredInvalidKeys[$op] ?? []
      );
      $extraKeys = array_diff(array_keys($link), $expectKeys);
      $this->assertEquals([], $extraKeys, "$msg: Link has unrecognized keys: " . json_encode(array_values($extraKeys)));
    }
  }

};
