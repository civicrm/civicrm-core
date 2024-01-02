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
 * The core concept of the system is an action performed on an object. Typically this will be a "data model" object
 * as specified in the API specs. We attempt to keep the number and type of actions consistent
 * and similar across all objects (thus providing both reuse and standards)
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Action {

  /**
   * Different possible actions are defined here.
   *
   * @var int
   */
  public const
    NONE = 0,
    ADD = 1,
    UPDATE = 2,
    VIEW = 4,
    DELETE = 8,
    BROWSE = 16,
    ENABLE = 32,
    DISABLE = 64,
    EXPORT = 128,
    BASIC = 256,
    ADVANCED = 512,
    PREVIEW = 1024,
    FOLLOWUP = 2048,
    MAP = 4096,
    PROFILE = 8192,
    COPY = 16384,
    RENEW = 32768,
    DETACH = 65536,
    REVERT = 131072,
    CLOSE = 262144,
    REOPEN = 524288,
    MAX_ACTION = 1048575;

  //make sure MAX_ACTION = 2^n - 1 ( n = total number of actions )

  /**
   * Map the action names to the relevant constant. We perform
   * bit manipulation operations so we can perform multiple
   * actions on the same object if needed
   *
   * @var array
   *
   */
  public static $_names = [
    'add' => self::ADD,
    'update' => self::UPDATE,
    'view' => self::VIEW,
    'delete' => self::DELETE,
    'browse' => self::BROWSE,
    'enable' => self::ENABLE,
    'disable' => self::DISABLE,
    'export' => self::EXPORT,
    'preview' => self::PREVIEW,
    'map' => self::MAP,
    'copy' => self::COPY,
    'profile' => self::PROFILE,
    'renew' => self::RENEW,
    'detach' => self::DETACH,
    'revert' => self::REVERT,
    'close' => self::CLOSE,
    'reopen' => self::REOPEN,
    'advanced' => self::ADVANCED,
  ];

  private static function getInfo(): array {
    Civi::$statics[__CLASS__ . 'Info'] ??= [
      self::ADD => [
        'name' => 'add',
        'label' => ts('Add'),
        'weight' => 0,
        'icon' => 'fa-plus',
      ],
      self::UPDATE => [
        'name' => 'update',
        'label' => ts('Edit'),
        'weight' => -10,
        'icon' => 'fa-pencil',
      ],
      self::VIEW => [
        'name' => 'view',
        'label' => ts('View'),
        'weight' => -20,
        'icon' => 'fa-external-link',
      ],
      self::DELETE => [
        'name' => 'delete',
        'label' => ts('Delete'),
        'weight' => 100,
        'icon' => 'fa-trash',
      ],
      self::BROWSE => [
        'name' => 'browse',
        'label' => ts('Browse'),
        'weight' => 0,
        'icon' => 'fa-list',
      ],
      self::ENABLE => [
        'name' => 'enable',
        'label' => ts('Enable'),
        'weight' => 40,
        'icon' => 'fa-repeat',
      ],
      self::DISABLE => [
        'name' => 'disable',
        'label' => ts('Disable'),
        'weight' => 40,
        'icon' => 'fa-ban',
      ],
      self::EXPORT => [
        'name' => 'export',
        'label' => ts('Export'),
        'weight' => 0,
        'icon' => 'fa-download',
      ],
      self::PREVIEW => [
        'name' => 'preview',
        'label' => ts('Preview'),
        'weight' => 0,
        'icon' => 'fa-eye',
      ],
      self::MAP => [
        'name' => 'map',
        'label' => ts('Map'),
        'weight' => 10,
        'icon' => 'fa-cog',
      ],
      self::COPY => [
        'name' => 'copy',
        'label' => ts('Copy'),
        'weight' => 20,
        'icon' => 'fa-clone',
      ],
      self::PROFILE => [
        'name' => 'profile',
        'label' => ts('Profile'),
        'weight' => 0,
        'icon' => 'fa-files-o',
      ],
      self::RENEW => [
        'name' => 'renew',
        'label' => ts('Renew'),
        'weight' => 10,
        'icon' => 'fa-undo',
      ],
      self::DETACH => [
        'name' => 'detach',
        'label' => ts('Move'),
        'weight' => 0,
        'icon' => 'fa-random',
      ],
      self::REVERT => [
        'name' => 'revert',
        'label' => ts('Revert'),
        'weight' => 0,
        'icon' => 'fa-refresh',
      ],
      self::CLOSE => [
        'name' => 'close',
        'label' => ts('Close'),
        'weight' => 0,
        'icon' => 'fa-folder',
      ],
      self::REOPEN => [
        'name' => 'reopen',
        'label' => ts('Reopen'),
        'weight' => 0,
        'icon' => 'fa-folder-open-o',
      ],
    ];
    return Civi::$statics[__CLASS__ . 'Info'];
  }

  /**
   * Called by the request object to translate a string into a mask.
   *
   * @param string $str
   *   The action to be resolved.
   *
   * @return int
   *   the action mask corresponding to the input string
   */
  public static function resolve($str) {
    $action = 0;
    if ($str) {
      $items = explode('|', $str);
      $action = self::map($items);
    }
    return $action;
  }

  /**
   * Given a string or an array of strings, determine the bitmask
   * for this set of actions
   *
   * @param mixed $item
   *   Either a single string or an array of strings.
   *
   * @return int
   *   the action mask corresponding to the input args
   */
  public static function map($item) {
    $mask = 0;

    if (is_array($item)) {
      foreach ($item as $it) {
        $mask |= self::mapItem($it);
      }
      return $mask;
    }
    else {
      return self::mapItem($item);
    }
  }

  /**
   * Given a string lookup the bitmask for the action name.
   * e.g. "add" returns self::ADD.
   *
   * @param string $name
   *
   * @return int
   */
  public static function mapItem($name) {
    foreach (self::getInfo() as $mask => $info) {
      if ($info['name'] === $name) {
        return $mask;
      }
    }
    return self::NONE;
  }

  /**
   * Given an action mask, get the name which describes it,
   * e.g. self::ADD returns 'add'.
   *
   * @param int $mask
   * @return string
   */
  public static function description($mask) {
    return self::getInfo()[$mask]['name'] ?? 'NO DESCRIPTION SET';
  }

  /**
   * Given a set of links and a mask, return the html action string for
   * the links associated with the mask
   *
   * @param array $links
   *   The set of link items.
   * @param int|null $mask
   *   The mask to be used. a null mask means all items.
   * @param array $values
   *   The array of values for parameter substitution in the link items.
   * @param string $extraULName
   *   Enclosed extra links in this UL.
   * @param bool $enclosedAllInSingleUL
   *   Force to enclosed all links in single UL.
   *
   * @param null $op
   * @param null $objectName
   * @param int $objectId
   * @param string $iconMode
   *   - `text`: even if `icon` is set for a link, display the `name`
   *   - `icon`: display only the `icon` for each link if it's available, and
   *     don't tuck anything under "more >"
   *   - `both`: if `icon` is available, display it next to the `name` for each
   *     link
   *
   * @return string
   *   the html string
   */
  public static function formLink(
    $links,
    $mask,
    $values,
    $extraULName = 'more',
    $enclosedAllInSingleUL = FALSE,
    $op = NULL,
    $objectName = NULL,
    $objectId = NULL,
    $iconMode = 'text'
  ) {
    if (empty($links)) {
      return NULL;
    }

    // make links indexed sequentially instead of by bitmask
    // otherwise it's next to impossible to reliably add new ones
    $seqLinks = [];
    foreach ($links as $bit => $link) {
      $link['bit'] = $bit;
      $seqLinks[] = $link;
    }

    if ($op && $objectName && $objectId) {
      CRM_Utils_Hook::links($op, $objectName, $objectId, $seqLinks, $mask, $values);
    }

    $url = [];

    usort($seqLinks, static function ($a, $b) {
      return (int) ((int) ($a['weight']) > (int) ($b['weight']));
    });

    foreach ($seqLinks as $i => $link) {
      $isActive = $link['is_active'] ?? TRUE;
      if ($isActive && (!$mask || !array_key_exists('bit', $link) || ($mask & $link['bit']))) {
        $extra = isset($link['extra']) ? self::replace($link['extra'], $values) : NULL;

        $frontend = isset($link['fe']);

        if (isset($link['qs']) && !CRM_Utils_System::isNull($link['qs'])) {
          $urlPath = CRM_Utils_System::url(self::replace($link['url'], $values),
            self::replace($link['qs'], $values), FALSE, NULL, TRUE, $frontend
          );
        }
        else {
          $urlPath = CRM_Utils_Array::value('url', $link, '#');
        }

        $classes = 'action-item crm-hover-button';
        if (isset($link['ref'])) {
          $classes .= ' ' . strtolower($link['ref']);
        }

        //get the user specified classes in.
        if (isset($link['class'])) {
          $className = is_array($link['class']) ? implode(' ', $link['class']) : $link['class'];
          $classes .= ' ' . strtolower($className);
        }

        if ($urlPath !== '#' && $frontend) {
          $extra .= ' target="_blank"';
        }
        // Hack to make delete dialogs smaller
        if (strpos($urlPath, '/delete') || strpos($urlPath, 'action=delete')) {
          $classes .= " small-popup";
        }

        $linkContent = $link['name'];
        if (!empty($link['icon'])) {
          if ($iconMode === 'icon') {
            $linkContent = CRM_Core_Page::crmIcon($link['icon'], $link['name'], TRUE, ['title' => '']);
          }
          elseif ($iconMode === 'both') {
            $linkContent = CRM_Core_Page::crmIcon($link['icon']) . ' ' . $linkContent;
          }
        }

        $url[] = sprintf('<a href="%s" class="%s" %s' . $extra . '>%s</a>',
          $urlPath,
          $classes,
          !empty($link['title']) ? "title='{$link['title']}' " : '',
          $linkContent
        );
      }
    }

    $mainLinks = $url;
    if ($enclosedAllInSingleUL) {
      $allLinks = '';
      CRM_Utils_String::append($allLinks, '</li><li>', $mainLinks);
      $allLinks = "{$extraULName}<ul class='panel'><li>{$allLinks}</li></ul>";
      $result = "<span class='btn-slide crm-hover-button'>{$allLinks}</span>";
    }
    else {
      $extra = '';
      if ($iconMode !== 'icon') {
        $extraLinks = array_splice($url, 2);
        if (count($extraLinks) > 1) {
          $mainLinks = array_slice($url, 0, 2);
          CRM_Utils_String::append($extra, '</li><li>', $extraLinks);
          $extra = "{$extraULName}<ul class='panel'><li>{$extra}</li></ul>";
        }
      }
      $resultLinks = '';
      CRM_Utils_String::append($resultLinks, '', $mainLinks);
      if ($extra) {
        $result = "<span>{$resultLinks}</span><span class='btn-slide crm-hover-button'>{$extra}</span>";
      }
      else {
        $result = "<span>{$resultLinks}</span>";
      }
    }

    return $result;
  }

  /**
   * Given a set of links and a mask, return a filtered (by mask) array containing the final links with parsed values
   *   and calling hooks as appropriate.
   * Use this when passing a set of action links to the API or to the form without adding html formatting.
   *
   * @param array $links
   *   The set of link items.
   * @param int $mask
   *   The mask to be used. a null mask means all items.
   * @param array $values
   *   The array of values for parameter substitution in the link items.
   * @param string|null $op
   * @param string|null $objectName
   * @param int $objectId
   *
   * @return array|null
   *   The array describing each link
   */
  public static function filterLinks(
    $links,
    $mask,
    $values,
    $op = NULL,
    $objectName = NULL,
    $objectId = NULL
  ) {
    if (empty($links)) {
      return NULL;
    }

    // make links indexed sequentially instead of by bitmask
    // otherwise it's next to impossible to reliably add new ones
    $seqLinks = [];
    foreach ($links as $bit => $link) {
      $link['bit'] = $bit;
      $seqLinks[] = $link;
    }

    if ($op && $objectName && $objectId) {
      CRM_Utils_Hook::links($op, $objectName, $objectId, $seqLinks, $mask, $values);
    }

    foreach ($seqLinks as $i => $link) {
      if (!$mask || !array_key_exists('bit', $link) || ($mask & $link['bit'])) {
        $seqLinks[$i]['extra'] = isset($link['extra']) ? self::replace($link['extra'], $values) : NULL;

        if (isset($link['qs']) && !CRM_Utils_System::isNull($link['qs'])) {
          $seqLinks[$i]['url'] = self::replace($link['url'], $values);
          $seqLinks[$i]['qs'] = self::replace($link['qs'], $values);
        }
      }
      else {
        unset($seqLinks[$i]);
      }
    }

    return $seqLinks;
  }

  /**
   * Given a string and an array of values, substitute the real values
   * in the placeholder in the str in the CiviCRM format
   *
   * @param string $str
   *   The string to be replaced.
   * @param array $values
   *   The array of values for parameter substitution in the str.
   *
   * @return string
   *   the substituted string
   */
  public static function &replace(&$str, &$values) {
    foreach ($values as $n => $v) {
      $str = str_replace("%%$n%%", ($v ?? ''), ($str ?? ''));
    }
    return $str;
  }

  /**
   * Get the mask for a permission (view, edit or null)
   *
   * @param array $permissions
   *
   * @return int
   *   The mask for the above permission
   */
  public static function mask($permissions) {
    $mask = NULL;
    if (!is_array($permissions) || CRM_Utils_System::isNull($permissions)) {
      return $mask;
    }
    //changed structure since we are handling delete separately - CRM-4418
    if (in_array(CRM_Core_Permission::VIEW, $permissions)) {
      $mask |= self::VIEW | self::EXPORT | self::BASIC | self::ADVANCED | self::BROWSE | self::MAP | self::PROFILE;
    }
    if (in_array(CRM_Core_Permission::DELETE, $permissions)) {
      $mask |= self::DELETE;
    }
    if (in_array(CRM_Core_Permission::EDIT, $permissions)) {
      //make sure we make self::MAX_ACTION = 2^n - 1
      //if we add more actions; ( n = total number of actions )
      $mask |= (self::MAX_ACTION & ~self::DELETE);
    }

    return $mask;
  }

  /**
   * @param int $mask
   * @return string|null
   */
  public static function getLabel(int $mask): ?string {
    return self::getInfo()[$mask]['label'] ?? NULL;
  }

  /**
   * @param int $mask
   * @return int|null
   */
  public static function getWeight(int $mask): ?string {
    return self::getInfo()[$mask]['weight'] ?? NULL;
  }

  /**
   * @param int $mask
   * @return int|null
   */
  public static function getIcon(int $mask): ?string {
    return self::getInfo()[$mask]['icon'] ?? NULL;
  }

  /**
   * Builds a title based on action and entity title, e.g. "Update Contact"
   *
   * @param int $action
   * @param string $entityTitle
   * @return string|null
   */
  public static function getTitle(int $action, string $entityTitle): ?string {
    switch ($action) {
      case self::ADD:
        return ts('Add %1', [1 => $entityTitle]);

      case self::UPDATE:
        return ts('Update %1', [1 => $entityTitle]);

      case self::VIEW:
        return ts('View %1', [1 => $entityTitle]);

      case self::DELETE:
        return ts('Delete %1', [1 => $entityTitle]);

      case self::BROWSE:
        return ts('Browse %1', [1 => $entityTitle]);

      case self::ENABLE:
        return ts('Enable %1', [1 => $entityTitle]);

      case self::DISABLE:
        return ts('Disable %1', [1 => $entityTitle]);

      case self::EXPORT:
        return ts('Export %1', [1 => $entityTitle]);

      case self::PREVIEW:
        return ts('Preview %1', [1 => $entityTitle]);

      case self::MAP:
        return ts('Map %1', [1 => $entityTitle]);

      case self::COPY:
        return ts('Copy %1', [1 => $entityTitle]);

      case self::PROFILE:
        return ts('Profile %1', [1 => $entityTitle]);

      case self::RENEW:
        return ts('Renew %1', [1 => $entityTitle]);

      case self::DETACH:
        return ts('Move %1', [1 => $entityTitle]);

      case self::REVERT:
        return ts('Revert %1', [1 => $entityTitle]);

      case self::CLOSE:
        return ts('Close %1', [1 => $entityTitle]);

      case self::REOPEN:
        return ts('Reopen %1', [1 => $entityTitle]);
    }
    return NULL;
  }

}
