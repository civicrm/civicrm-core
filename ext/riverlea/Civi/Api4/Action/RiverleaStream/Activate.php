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

namespace Civi\Api4\Action\RiverleaStream;

/**
 * @inheritDoc
 *
 * NOTE: in practice it's a bit weird this is a batch action, as each doTask will
 * supercede the override the previous one.
 *
 * But it works nicely with other API tooling and allows for
 * activating where ID = 5 OR where name = my_stream OR ->first based
 * on your chosen order by...
 */
class Activate extends \Civi\Api4\Generic\BasicBatchAction {

  /**
   * @var string
   *
   * Activate this stream as 'backend' theme, 'frontend' theme
   * or 'both'
   */
  protected string $backOrFront = 'backend';

  /**
   * @inheritdoc
   */
  protected function getSelect(): array {
    return [
      'name', 'label',
      'extension', 'file_prefix',
      'css_file', 'css_file_dark',
      'vars', 'vars_dark',
      'custom_css', 'custom_css_dark',
      'dark_frontend', 'dark_backend',
    ];
  }

  /**
   * @inheritdoc
   *
   * Set this stream as the theme for frontend or backend
   */
  protected function doTask($stream): array {
    $streamName = $stream['name'];

    // validate the theme is available for this stream
    $available = \Civi::service('themes')->getAvailable();
    if (empty($available[$streamName])) {
      throw new \CRM_Core_Exception("This stream is not available to select as a Civi theme. Maybe it has been disabled?");
    }

    $themeSettings = [];

    switch ($this->backOrFront) {
      case 'backend':
        $themeSettings[] = 'theme_backend';
        break;

      case 'frontend':
        $themeSettings[] = 'theme_frontend';
        break;

      case 'both':
        $themeSettings[] = 'theme_backend';
        $themeSettings[] = 'theme_frontend';
        break;

      default:
        throw new \CRM_Core_Exception("Invalid value '{$this->backOrFront}' for back or front - should be 'backend', 'frontend', or 'both'");
    }

    foreach ($themeSettings as $setting) {
      \Civi::settings()->set($setting, $streamName);
    }

    return [];
  }

}
