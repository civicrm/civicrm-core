<?php

/**
 * Define the security-constraints to apply to user-supplied Smarty content.
 *
 * At time of writing, we have a complication -- parallel support for Smarty 2/3/4/5. Each
 * version has slightly different functionality.
 *
 * To bridge the gap, we define a general policy -- and then map it into each Smarty implementation.
 */
class CRM_Core_Smarty_UserContentPolicy extends \Civi\Core\Service\AutoService {

  /**
   * This is an array of trusted PHP functions.
   * If empty all functions are allowed.
   * To disable all PHP functions set $php_functions = null.
   *
   * @var array
   */
  public $php_functions = [
    'array',
    'list',
    'isset',
    'empty',
    'count',
    'sizeof',
    'in_array',
    'is_array',
    'true',
    'false',
    'null',
  ];

  /**
   * This is an array of trusted PHP modifiers.
   * If empty all modifiers are allowed.
   * To disable all modifier set $php_modifiers = null.
   *
   * @var array
   */
  public $php_modifiers = [
    'escape',
    'count',
    'sizeof',
    'nl2br',
  ];

  /**
   * This is an array of disabled tags.
   * If empty no restriction by disabled_tags.
   *
   * @var array
   */
  public $disabled_tags = ['crmAPI'];

  public $allow_constants = FALSE;

  public $allow_super_globals = FALSE;

  private $old_settings = NULL;

  /**
   * @service civi.smarty.userContent
   */
  public static function create(): CRM_Core_Smarty_UserContentPolicy {
    $instance = new CRM_Core_Smarty_UserContentPolicy();

    $event = \Civi\Core\Event\GenericHookEvent::create(['policy' => $instance]);
    Civi::dispatcher()->dispatch('hook_civicrm_userContentPolicy', $event);

    return $instance;
  }

  public function enable(): void {
    $smarty = CRM_Core_Smarty::singleton();
    switch ($smarty->getVersion()) {
      case 2:
        $smarty->security = TRUE;
        return;

      case 3:
      case 4:
        $smarty->enableSecurity($this->createSmartyPolicy34());
        return;

      case 5:
        $smarty->enableSecurity($this->createSmartyPolicy5());
        return;
    }
  }

  public function disable(): void {
    $smarty = CRM_Core_Smarty::singleton();
    switch ($smarty->getVersion()) {
      case 2:
        $smarty->security = FALSE;
        return;

      case 3:
      case 4:
        $smarty->disableSecurity();
        return;

      case 5:
        $smarty->disableSecurity();
        return;
    }

  }

  protected function createSmartyPolicy34(): string {
    $obj = new class(NULL) extends Smarty_Security {
      public function __construct($smarty) {
        /** @var \CRM_Core_Smarty_UserContentPolicy $policy */
        $policy = Civi::service('civi.smarty.userContent');

        $this->php_functions = $policy->php_functions;
        $this->php_modifiers = $policy->php_modifiers;
        $this->disabled_tags = $policy->disabled_tags;

        $this->static_classes = NULL;
        $this->allow_constants = $policy->allow_constants;
        $this->allow_super_globals = $policy->allow_super_globals;
      }

    };
    return get_class($obj);
  }

  protected function createSmartyPolicy5(): string {

    $obj = new class(NULL) extends \Smarty\Security {

      public function __construct($smarty) {
        /** @var \CRM_Core_Smarty_UserContentPolicy $policy */
        $policy = Civi::service('civi.smarty.userContent');

        // This feels counterintuitive. Eileen thinks it may be a miscommunication.
        // Functionally, consider that (a) security is enabled/disabled/enabled/disabled
        // but (b) the registered plugins don't actually change.

        // foreach ($policy->php_functions as $phpFunction) {
        //   $smarty->registerPlugin('modifier', $phpFunction, $phpFunction);
        // }
        // foreach ($policy->php_modifiers as $modifier) {
        //   $smarty->registerPlugin('modifier', $modifier, $modifier);
        // }

        $this->static_classes = NULL;
        $this->allow_constants = $policy->allow_constants;
        $this->allow_super_globals = $policy->allow_super_globals;
      }

    };
    return get_class($obj);
  }

  /**
   * Smarty 3+4 have option to disable tags in secure mode, but Smarty 2 doesn't.
   * So for any potentially-sensitive tags, we support an alternate mechanism to check access.
   *
   * @param string $tag
   * @return void
   * @throws \Exception
   */
  public static function assertTagAllowed(string $tag): void {
    $smarty = CRM_Core_Smarty::singleton();
    $hasSecurity = ($smarty->getVersion() > 2) ? (bool) $smarty->security_policy : $smarty->security;
    if (!$hasSecurity) {
      return;
    }

    $policy = Civi::service('civi.smarty.userContent');
    if (in_array($tag, $policy->disabled_tags)) {
      throw new \Exception("Tag '{$tag}' is not allowed in secure mode.");
    }
  }

}
