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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_Cms extends CRM_Utils_Check_Component {

  /**
   * For sites running in WordPress, make sure the configured base page exists.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public static function checkWpBasePage() {
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework != 'WordPress') {
      return [];
    }
    if (is_multisite()) {
      // There are a lot potential configurations in a multisite context where
      // this could show a false positive.  This completely skips multisite for
      // now.
      return [];
    }

    switch (self::pageExists($config->wpBasePage)) {
      case 1:
        // Page is here and published
        return [];

      case 0:
        $messageText = [
          ts(
            'CiviCRM relies upon a <a href="%1%2">base page in WordPress</a>, but it is not published.',
            [
              1 => $config->userFrameworkBaseURL,
              2 => $config->wpBasePage,
            ]
          ),
        ];
        break;

      case -1:
        // Page is missing, but let's look around to see if the default is there
        // --either the default as modified by civicrm_basepage_slug or the
        // default default, `civicrm`.
        $cmsSettings = CRM_Utils_System::url(
          'civicrm/admin/setting',
          $query = ['reset' => 1],
          FALSE,
          NULL,
          TRUE,
          FALSE,
          TRUE
        );
        $messageText = [
          ts(
            'CiviCRM relies upon a base page in WordPress at %1%2, but it is missing.',
            [
              1 => $config->userFrameworkBaseURL,
              2 => $config->wpBasePage,
            ]
          ),
        ];

        $altSlugs = array_unique([
          apply_filters('civicrm_basepage_slug', 'civicrm'),
          'civicrm',
        ]);

        if (in_array($config->wpBasePage, $altSlugs)) {
          $messageText[] = ts(
            'If you have an alternative base page, it can be set in the <a href="%2">WordPress integration settings</a>.',
            [
              1 => $config->userFrameworkBaseURL,
              2 => $cmsSettings,
            ]
          );
        }
        else {
          foreach ($altSlugs as $slug) {
            $exists = self::pageExists($slug);
            if ($exists >= 0) {
              // One of the possible defaults is here, published or not.
              $messageText[] = ts(
                'The default is %1%2, which <a href="%1%2">does exist on this site</a>.',
                [
                  1 => $config->userFrameworkBaseURL,
                  2 => $slug,
                ]
              );
              if ($exists == 0) {
                $messageText[] = ts('However, it is not published.');
              }
              // We've found one, and if the `civicrm_basepage_slug` filter has
              // modified the default, we should go with it.
              break;
            }
          }
          if ($exists == -1) {
            // We went through the default(s) and couldn't find one.  Defer to
            // the one modified by the filter.
            $messageText[] = ts(
              'The default is %1%2, but that does not exist on this site either.',
              [
                1 => $config->userFrameworkBaseURL,
                2 => $altSlugs[0],
              ]
            );
          }

          $messageText[] = ts(
            'You can set the correct base page in the <a href="%1">WordPress integration settings</a>.',
            [1 => $cmsSettings]
          );
        }
    }

    return [
      new CRM_Utils_Check_Message(
        __FUNCTION__,
        implode(' ', $messageText),
        ts('WordPress Base Page Missing'),
        \Psr\Log\LogLevel::ERROR,
        'fa-wordpress'
      ),
    ];
  }

  /**
   * See if a page exists and is published.
   *
   * @param string $slug
   *   The page path.
   * @return int
   *   -1 if it's missing
   *   0 if it's present but not published
   *   1 if it's present and published
   */
  private static function pageExists($slug) {
    $basePage = get_page_by_path($slug);
    if (!$basePage) {
      return -1;
    }

    return (int) ($basePage->post_status == 'publish');
  }

}
