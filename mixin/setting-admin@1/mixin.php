<?php

/**
 * The "setting-admin" mixin defines a standard idiom for managing extension settings:
 *
 * 1. Create a permission "administer {myext}" ("Administer {My Extension}").
 * 2. Create a page "civicrm/admin/setting/{myext}" (via `CRM_Admin_Form_Generic`)
 * 3. Assign all settings from "{myext}" to appear on the page.
 * 4. Create a link "Administer > System Settings" to "{My Extension} Settings"
 *
 * (The values of "{myext}" and "{My Extension}" come from info.xml's `<file>` and `<name>`.)
 *
 * If you don't like the defaults, then there are a few override points:
 *
 * - If you manually create permission "administer {myext}", then your label/description takes precedence.
 * - If you manually register route "civicrm/admin/setting/{myext}", then your definition takes precedence.
 * - If you manually configure a setting with `settings_page`, then that setting will move to the other page.
 *   (To make a hidden setting, specify `settings_page => []`.)
 * - If you manually add "civicrm/admin/setting/{myext}" to the menu, then your link takes precedence.
 *
 * Additionally, there is experimental support for overrides in info.xml. (Respected by v1.0.0 but not guaranteed future.)
 *
 *   <civix><setting-page-title>My Custom Title</setting-page-title></civix>
 *
 * @mixinName setting-admin
 * @mixinVersion 1.0.0
 * @since 5.67
 */

namespace Civi\Mixin\SettingAdminV1;

use Civi;

class About {

  /**
   * @var \CRM_Extension_MixInfo
   */
  private $mixInfo;

  /**
   * @var \CRM_Extension_Info
   */
  private $info;

  /**
   * @param \CRM_Extension_MixInfo $mixInfo
   */
  public static function instance(\CRM_Extension_MixInfo $mixInfo): About {
    $about = new About();
    $about->mixInfo = $mixInfo;
    $about->info = \CRM_Extension_System::singleton()->getMapper()->keyToInfo($mixInfo->longName);
    return $about;
  }

  public function getPath(): string {
    return 'civicrm/admin/setting/' . $this->mixInfo->shortName;
  }

  public function getPerm(): string {
    return 'administer ' . $this->mixInfo->shortName;
  }

  public function getLabel(): string {
    return $this->info->label ? _ts($this->info->label, ['domain' => $this->info->key]) : $this->info->key;
  }

  public function getPageTitle(): string {
    // Changing the title any other way is slightly annoying because you have to override both route+nav.
    // It might be nice if one (route or menu) reliably inherited its title from the other...
    if (!empty($this->info->civix['setting-page-title'])) {
      return $this->info->civix['setting-page-title'];
      // Could call _ts(..., [domain=>...]), but translation appears to happen at another level,
      // and double-translation might confuse multilingual.
    }
    return ts('%1 Settings', [1 => $this->getLabel()]);
  }

  public function getRoute(): array {
    return [
      'title' => $this->getPageTitle(),
      'page_callback' => 'CRM_Admin_Form_Generic',
      'access_arguments' => [['administer CiviCRM', $this->getPerm()], 'or'],
      'adminGroup' => 'System Settings',
      'desc' => _ts($this->info->description ?: ''),
    ];
  }

  public function getNavigation(): array {
    return [
      'label' => $this->getPageTitle(),
      'name' => sprintf('%s_setting_admin', $this->mixInfo->shortName),
      'url' => $this->getPath() . '?reset=1',
      // 'icon' => 'crm-i fa-wrench', // None of the other "System Settings" have icons, so we don't.
      // 'permission' => ['administer CiviCRM', $this->getPerm()],
      'permission' => "administer CiviCRM,{$this->getPerm()}",
      'permission_operator' => 'OR',
    ];
  }

}

class Nav {

  /**
   * Visit all items in the nav-tree.
   *
   * @param array $items
   * @param callable $callback
   *   function(array &$item): mixed
   *   To short-circuit execution, the callback should return a non-null value.
   * @return string|null
   *   Return NULL by default. If the walk was short-circuited, then return that value.
   */
  public static function walk(&$items, callable $callback) {
    foreach ($items as &$item) {
      $result = $callback($item);
      if ($result !== NULL) {
        return $result;
      }
      if (!empty($item['child'])) {
        $result = static::walk($item['child'], $callback);
        if ($result !== NULL) {
          return $result;
        }
      }
    }
    return NULL;
  }

}

/**
 * @param \CRM_Extension_MixInfo $mixInfo
 * @param \CRM_Extension_BootCache $bootCache
 */
return function ($mixInfo, $bootCache) {

  // Register the setting page ("civicrm/admin/setting/{myext}").
  Civi::dispatcher()->addListener('&hook_civicrm_alterMenu', function (array &$items) use ($mixInfo) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $about = About::instance($mixInfo);
    if (!isset($items[$about->getPath()])) {
      $items[$about->getPath()] = $about->getRoute();
    }
  }, -1000);

  // Define a permission "administer {myext}"
  Civi::dispatcher()->addListener('&hook_civicrm_permission', function (array &$permissions) use ($mixInfo) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $about = About::instance($mixInfo);
    $perm = 'administer ' . $mixInfo->shortName;
    if (!isset($permissions[$perm])) {
      $permissions[$perm] = ts('%1: Administer settings', [1 => $about->getLabel()]);
    }
  }, -1000);

  // Any settings with "group=={myext}" should be added to our setting page (unless overridden).
  // By default, 'weight' is based on the order-of-declaration (spaced out with increments of 10).
  Civi::dispatcher()->addListener('&hook_civicrm_alterSettingsMetaData', function(array &$settingsMetaData) use ($mixInfo) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $weight = 1000;
    $weightInterval = 10;

    foreach ($settingsMetaData as &$setting) {
      if (($setting['group'] ?? '') === $mixInfo->shortName) {
        if (!array_key_exists('settings_pages', $setting)) {
          $setting['settings_pages'][$mixInfo->shortName] = [
            'weight' => $weight,
          ];
          $weight += $weightInterval;
        }
      }
    }
  }, -1000);

  // Add navigation-item ('civicrm/admin/setting/{myext}') unless you've already done so.
  Civi::dispatcher()->addListener('&hook_civicrm_navigationMenu', function (&$menu) use ($mixInfo) {
    if (!$mixInfo->isActive()) {
      return;
    }

    $about = About::instance($mixInfo);
    $newItem = $about->getNavigation() + ['active' => 1];

    // Skip if we're already in the menu. (Ignore optional suffix `?reset=1`)
    $found = Nav::walk($menu, function(&$item) use ($about) {
      if (!isset($item['attributes']['url'])) {
        return NULL;
      }
      return strpos($item['attributes']['url'], $about->getPath()) === 0 ? 'found' : NULL;
    });
    if ($found) {
      return;
    }

    Nav::walk($menu, function(&$item) use ($newItem) {
      if ($item['attributes']['name'] === 'System Settings') {
        $item['child'][] = ['attributes' => $newItem];
        return 'done';
      }
    });
  }, -1000);

};
