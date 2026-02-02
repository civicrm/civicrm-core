<?php

namespace Civi\Riverlea\Page;

use Civi\Core\SettingsMetadata;
use CRM_riverlea_ExtensionUtil as E;

class ThemeAdmin extends \CRM_Core_Page {

  public function run() {
    // TODO: create a bundle? OR a bundler for webcomponents?
    $resources = \Civi::resources();
    $resources->addScriptFile(E::SHORT_NAME, 'js/utils.js', ['weight' => 100]);
    $resources->addScriptFile(E::SHORT_NAME, 'js/editor.js', ['weight' => 200]);
    $resources->addScriptFile(E::SHORT_NAME, 'js/stream-list.js', ['weight' => 200]);
    $resources->addStyleFile(E::SHORT_NAME, 'css/stream-list.css');

    if (!\Civi::service('riverlea.style_loader')->isActive()) {
      \CRM_Core_Session::setStatus(E::ts('Stream previewer will not work whilst using a legacy theme'), ts('Stream Previews'), 'warning');
    }

    // get settings for the theme page
    $allSettings = SettingsMetadata::getMetadata([], NULL, TRUE, FALSE, TRUE);
    $themeSettings = array_filter($allSettings, fn ($setting) => isset($setting['settings_pages']['theme']));
    // sort by theme page weight from the meta
    \usort($themeSettings, fn ($a, $b) => $a['settings_pages']['theme']['weight'] - $b['settings_pages']['theme']['weight']);

    foreach ($themeSettings as &$setting) {
      $value = \Civi::settings()->get($setting['name']);

      // render option values if applicable
      // use raw value if not a valid option
      if ($setting['options']) {
        $valueLabel = $setting['options'][$value] ?? E::ts("%1 [unrecognised option]", [1 => $value]);
      }
      else {
        $valueLabel = $value;
      }

      $setting['value'] = $value;
      $setting['value_label'] = $valueLabel;
    }

    $this->assign('settings', $themeSettings);
    $this->assign('settingsFormUrl', \Civi::url('backend://civicrm/admin/settings/theme'));

    // when the settings form is submitted, the only way we have to refresh the values shown is by refreshing the whole page
    // TODO: create a component to show settings values (with an endpoint that can render them like the above) and then
    // refresh this component on submit
    \Civi::resources()->addScript('
      CRM.$(function($) {
        $(document).on("crmPopupFormSuccess", () => window.location.reload());
      });
    ');

    parent::run();
  }

}
