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

/**
 * This class provides the functionality to Grant access to CiviCRM components and other CiviCRM permissions.
 */
class CRM_ACL_Form_WordPress_Permissions extends CRM_Core_Form {

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $this->setTitle(ts('WordPress Access Control'));

    // Get the core permissions array
    $permissionsArray = self::getPermissionArray();
    $permissionsDesc = self::getPermissionArray(TRUE);

    // Get the WordPress roles, default capabilities and assign to the form
    global $wp_roles;
    if (!isset($wp_roles)) {
      $wp_roles = new WP_Roles();
    }
    foreach ($wp_roles->role_names as $role => $name) {
      // Unless it's Multisite, don't show the permissions options for administrator, as they have all permissions
      if (is_multisite() or $role !== 'administrator') {
        $roleObj = $wp_roles->get_role($role);
        if (!empty($roleObj->capabilities)) {
          foreach ($roleObj->capabilities as $ckey => $cname) {
            if (array_key_exists($ckey, $permissionsArray)) {
              $elementName = $role . '[' . $ckey . ']';
              $defaults[$elementName] = 1;
            }
          }
        }

        // Compose the checkbox array for each role, to assign to form
        $rolePerms[$role] = $permissionsArray;
        foreach ($rolePerms[$role] as $key => $value) {
          $elementName = $role . '[' . $key . ']';
          $this->add('checkbox', $elementName, $value);
        }
        $roles[$role] = $name;
      }
    }

    $this->setDefaults($defaults);

    $descArray = [];
    foreach ($permissionsDesc as $perm => $attr) {
      if (!empty($attr['description'])) {
        $descArray[$perm] = $attr['description'];
      }
    }

    // build table rows by merging role perms
    $rows = [];
    foreach ($rolePerms as $role => $perms) {
      foreach ($perms as $name => $title) {
        $rows[$name] = $title;
      }
    }

    // Build array keyed by permission
    $table = [];
    foreach ($rows as $perm => $label) {

      // Init row with permission label
      $table[$perm] = [
        'label' => $label,
        'roles' => [],
      ];

      // Add permission description and role names
      foreach ($roles as $key => $label) {
        if (isset($descArray[$perm])) {
          $table[$perm]['desc'] = $descArray[$perm];
        }
        $table[$perm]['roles'][] = $key;
      }

    }

    $this->assign('table', $table);
    $this->assign('rolePerms', $rolePerms);
    $this->assign('roles', $roles);

    $this->addButtons(
      [
        [
          'type' => 'next',
          'name' => ts('Save'),
          'spacing' => '',
          'isDefault' => FALSE,
        ],
      ]
    );

  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $permissionsArray = self::getPermissionArray();

    // Function to get Wordpress roles
    global $wp_roles;
    if (!isset($wp_roles)) {
      $wp_roles = new WP_Roles();
    }
    foreach ($wp_roles->role_names as $role => $name) {
      $roleObj = $wp_roles->get_role($role);

      //Remove all civicrm capabilities for the role, as there may be some capabilities checkbox unticked
      foreach ($permissionsArray as $key => $capability) {
        $roleObj->remove_cap($key);
      }

      //Add the selected wordpress capabilities for the role
      $rolePermissions = $params[$role] ?? [];
      if (!empty($rolePermissions)) {
        foreach ($rolePermissions as $key => $capability) {
          $roleObj->add_cap($key);
        }
      }

      if ($role == 'anonymous_user') {
        // Get the permissions into a format that matches what we get from WP
        $allWarningPermissions = CRM_Core_Permission::getAnonymousPermissionsWarnings();
        foreach ($allWarningPermissions as $key => $permission) {
          $allWarningPermissions[$key] = CRM_Utils_String::munge(strtolower($permission));
        }
        $warningPermissions = array_intersect($allWarningPermissions, array_keys($rolePermissions));
        $warningPermissionNames = [];
        foreach ($warningPermissions as $permission) {
          $warningPermissionNames[$permission] = $permissionsArray[$permission];
        }
        if (!empty($warningPermissionNames)) {
          CRM_Core_Session::setStatus(
            ts('The %1 role was assigned one or more permissions that may prove dangerous for users of that role to have. Please reconsider assigning %2 to them.', [
              1 => $wp_roles->role_names[$role],
              2 => implode(', ', $warningPermissionNames),
            ]),
            ts('Unsafe Permission Settings')
          );
        }
      }
    }

    // FIXME
    // Changed the 'access_civicrm_nav_link' capability in civicrm.php file
    // But for some reason, if i remove 'Access CiviCRM' administrator and save, it is showing
    // 'You do not have sufficient permissions to access this page'
    // which should not happen for Super Admin and Administrators, as checking permissions for Super
    // Admin and Administrators always gives TRUE
    wp_civicrm_capability();

    CRM_Core_Session::setStatus("", ts('Wordpress Access Control Updated'), "success");

    // rebuild the menus to comply with the new permissions/capabilites
    Civi::rebuild(['*' => TRUE, 'triggers' => FALSE, 'sessions' => FALSE])->execute();
    // ^^ The above is drop-in equivalent to tradition. But the below feels more consistent with commented intent:
    // Civi::rebuild(['menu' => TRUE, 'perms' => TRUE])->execute();

    CRM_Utils_System::redirect('admin.php?page=CiviCRM&q=civicrm/admin/access&reset=1');
    CRM_Utils_System::civiExit();
  }

  /**
   * Get the core civicrm permissions array.
   * This function should be shared from a similar one in
   * distmaker/utils/joomlaxml.php
   *
   * @param bool $descriptions
   *   Whether to return permission descriptions
   *
   * @return array
   *   civicrm permissions
   */
  public static function getPermissionArray($descriptions = FALSE) {

    $permissions = CRM_Core_Permission::basicPermissions(FALSE, $descriptions);

    $perms_array = [];
    foreach ($permissions as $perm => $title) {
      //order matters here, but we deal with that later
      $perms_array[CRM_Utils_String::munge(strtolower($perm))] = $title;
    }

    return $perms_array;
  }

}
