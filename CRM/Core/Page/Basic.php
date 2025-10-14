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
abstract class CRM_Core_Page_Basic extends CRM_Core_Page {

  protected $_action;

  /**
   * Name of the BAO to perform various DB manipulations.
   *
   * @return CRM_Core_DAO|string
   */
  abstract protected function getBAOName();

  /**
   * Get array of action links for the "browse" page.
   *
   * Transforms from the 'paths' in metadata to the
   * format expected by basic pages.
   *
   * @return array[]
   */
  public function &links() {
    $baoName = $this->getBAOName();
    if (!isset(Civi::$statics[$baoName]['actionLinks'])) {
      Civi::$statics[$baoName]['actionLinks'] = [];
      $title = $baoName::getEntityTitle();
      $paths = $baoName::getEntityPaths();
      unset($paths['add'], $paths['browse']);
      foreach ($paths as $action => $path) {
        $actionKey = CRM_Core_Action::map($action);
        if ($actionKey) {
          [$path, $query] = array_pad(explode('?', $path), 2, '');
          Civi::$statics[$baoName]['actionLinks'][$actionKey] = [
            'name' => CRM_Core_Action::getLabel($actionKey),
            'title' => CRM_Core_Action::getTitle($actionKey, $title),
            'url' => $path,
            'qs' => str_replace(['[', ']'], '%%', $query),
            'weight' => CRM_Core_Action::getWeight($actionKey),
          ];
        }
      }
      if (isset($baoName::getSupportedFields()['is_active'])) {
        foreach ([CRM_Core_Action::DISABLE, CRM_Core_Action::ENABLE] as $actionKey) {
          Civi::$statics[$baoName]['actionLinks'][$actionKey] = [
            'name' => CRM_Core_Action::getLabel($actionKey),
            'title' => CRM_Core_Action::getTitle($actionKey, $title),
            'ref' => 'crm-enable-disable',
            'weight' => CRM_Core_Action::getWeight($actionKey),
          ];
        }
      }
    }
    return Civi::$statics[$baoName]['actionLinks'];
  }

  /**
   * Name of the edit form class.
   *
   * @return string
   */
  abstract protected function editForm();

  /**
   * Name of the form.
   *
   * @return string
   */
  abstract protected function editName();

  /**
   * UserContext to pop back to.
   *
   * @param int $mode
   *   Mode that we are in.
   *
   * @return string
   */
  abstract protected function userContext($mode = NULL);

  /**
   * Get userContext params.
   *
   * @param int $mode
   *   Mode that we are in.
   *
   * @return string
   */
  public function userContextParams($mode = NULL) {
    return 'reset=1&action=browse';
  }

  /**
   * Allow objects to be added based on permission.
   *
   * @param int $id
   *   The id of the object.
   * @param int $name
   *   The name or title of the object.
   *
   * @return string
   *   permission value if permission is granted, else null
   */
  public function checkPermission($id, $name) {
    return CRM_Core_Permission::EDIT;
  }

  /**
   * Allows the derived class to add some more state variables to
   * the controller. By default does nothing, and hence is abstract
   *
   * @param CRM_Core_Controller $controller
   *   The controller object.
   */
  public function addValues($controller) {
  }

  /**
   * Class constructor.
   *
   * @param string $title
   *   Title of the page.
   * @param int $mode
   *   Mode of the page.
   *
   * @return \CRM_Core_Page_Basic
   */
  public function __construct($title = NULL, $mode = NULL) {
    parent::__construct($title, $mode);
  }

  /**
   * Run the basic page (run essentially starts execution for that page).
   */
  public function run() {
    // CRM-9034
    // do not see args or pageArgs being used, so we should
    // consider eliminating them in a future version
    $n = func_num_args();
    $args = ($n > 0) ? func_get_arg(0) : NULL;
    $pageArgs = ($n > 1) ? func_get_arg(1) : NULL;
    $sort = ($n > 2) ? func_get_arg(2) : NULL;
    // what action do we want to perform ? (store it for smarty too.. :)

    $id = $this->getIdAndAction();

    if ($this->_action & (CRM_Core_Action::VIEW |
        CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE |
        CRM_Core_Action::COPY |
        CRM_Core_Action::ENABLE |
        CRM_Core_Action::DISABLE |
        CRM_Core_Action::DELETE
      )
    ) {
      // use edit form for view, add or update or delete
      $this->edit($this->_action, $id);
    }
    else {
      // if no action or browse
      $this->browse(NULL, $sort);
    }

    return parent::run();
  }

  /**
   * Retrieve the action and ID from the request.
   *
   * Action is assigned to the template while we're at it.  This is pulled from
   * the `run()` method above.
   *
   * @return int
   *   The ID if present, or 0.
   * @throws \CRM_Core_Exception
   */
  public function getIdAndAction() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);

    $this->assign('selectedChild', CRM_Utils_Request::retrieve('selectedChild', 'Alphanumeric', $this));

    // get 'id' if present
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);

    if ($id) {
      if (!$this->checkPermission($id, NULL)) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to make changes to the record'));
      }
    }

    return $id;
  }

  /**
   * @return string
   */
  public function superRun() {
    return parent::run();
  }

  /**
   * Browse all entities.
   */
  public function browse() {
    $n = func_num_args();
    $action = ($n > 0) ? func_get_arg(0) : NULL;
    $sort = ($n > 0) ? func_get_arg(1) : NULL;
    $links = &$this->links();
    if ($action == NULL) {
      if (!empty($links)) {
        $action = array_sum(array_keys($links));
      }
    }
    if ($action & CRM_Core_Action::DISABLE) {
      $action -= CRM_Core_Action::DISABLE;
    }
    if ($action & CRM_Core_Action::ENABLE) {
      $action -= CRM_Core_Action::ENABLE;
    }

    $this->assign('rows', $this->getRows($sort, $action, $links));
  }

  /**
   * Given an object, get the actions that can be associated with this
   * object. Check the is_active and is_required flags to display valid
   * actions
   *
   * @param CRM_Core_DAO $object
   *   The object being considered.
   * @param int $action
   *   The base set of actions.
   * @param array $values
   *   The array of values that we send to the template.
   * @param array $links
   *   The array of links.
   * @param string $permission
   *   The permission assigned to this object.
   *
   * @param bool $forceAction
   */
  public function action(&$object, $action, &$values, &$links, $permission, $forceAction = FALSE) {
    $values['class'] = '';
    $newAction = $action;
    $hasDelete = $hasDisable = TRUE;

    if (!empty($values['name']) && in_array($values['name'], [
      'encounter_medium',
      'case_type',
      'case_status',
    ])) {
      static $caseCount = NULL;
      if (!isset($caseCount)) {
        $caseCount = CRM_Case_BAO_Case::caseCount(NULL, FALSE);
      }
      if ($caseCount > 0) {
        $hasDelete = $hasDisable = FALSE;
      }
    }

    $object_type = get_class($object);

    if (!$forceAction) {
      if (property_exists($object, 'is_reserved') && $object->is_reserved) {
        $values['class'] = 'reserved';
        // check if object is relationship type

        $exceptions = [
          'CRM_Contact_BAO_RelationshipType',
          'CRM_Core_BAO_LocationType',
          'CRM_Badge_BAO_Layout',
        ];

        if (in_array($object_type, $exceptions)) {
          $newAction = CRM_Core_Action::VIEW + CRM_Core_Action::UPDATE;
        }
        else {
          $newAction = 0;
          $values['action'] = '';
          return;
        }
      }
      else {
        if (property_exists($object, 'is_active')) {
          if ($object->is_active) {
            if ($hasDisable) {
              $newAction += CRM_Core_Action::DISABLE;
            }
          }
          else {
            $newAction += CRM_Core_Action::ENABLE;
          }
        }
      }
    }

    //CRM-4418, handling edit and delete separately.
    $permissions = [$permission];
    if ($hasDelete && ($permission == CRM_Core_Permission::EDIT)) {
      //previously delete was subset of edit
      //so for consistency lets grant delete also.
      $permissions[] = CRM_Core_Permission::DELETE;
    }

    // make sure we only allow those actions that the user is permissioned for
    $newAction = $newAction & CRM_Core_Action::mask($permissions);

    $values['action'] = CRM_Core_Action::formLink(
      $links,
      $newAction,
      ['id' => $object->id],
      'more',
      FALSE,
      "basic.$object_type.page",
      $object_type,
      $object->id
    );
  }

  /**
   * Edit this entity.
   *
   * @param int $mode
   *   What mode for the form ?.
   * @param int $id
   *   Id of the entity (for update, view operations).
   *
   * @param bool $imageUpload
   * @param bool $pushUserContext
   */
  public function edit($mode, $id = NULL, $imageUpload = FALSE, $pushUserContext = TRUE) {
    $controller = new CRM_Core_Controller_Simple($this->editForm(),
      $this->editName(),
      $mode,
      $imageUpload
    );

    // set the userContext stack
    if ($pushUserContext) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url($this->userContext($mode), $this->userContextParams($mode)));
    }
    if ($id !== NULL) {
      $controller->set('id', $id);
    }
    $controller->set('BAOName', $this->getBAOName());
    $this->addValues($controller);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  /**
   * @param $sort
   * @param $action
   * @param array $links
   *
   * @return array
   */
  protected function getRows($sort, $action, array $links): array {
    $baoString = $this->getBAOName();
    $object = new $baoString();

    $values = [];

    // lets make sure we get the stuff sorted by name if it exists
    $fields = &$object->fields();
    $key = '';
    if (!empty($fields['title'])) {
      $key = 'title';
    }
    elseif (!empty($fields['label'])) {
      $key = 'label';
    }
    elseif (!empty($fields['name'])) {
      $key = 'name';
    }

    if (trim($sort ?? '')) {
      $object->orderBy($sort);
    }
    elseif ($key) {
      $object->orderBy($key . ' asc');
    }

    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, FALSE);
    // find all objects
    $object->find();
    while ($object->fetch()) {
      if (!isset($object->mapping_type_id) ||
        // "1 for Search Builder"
        $object->mapping_type_id != 1
      ) {
        $permission = CRM_Core_Permission::EDIT;
        if ($key) {
          $permission = $this->checkPermission($object->id, $object->$key);
        }
        if ($permission) {
          $values[$object->id] = [];
          CRM_Core_DAO::storeValues($object, $values[$object->id]);

          if (is_a($object, 'CRM_Contact_DAO_RelationshipType')) {
            $values[$object->id]['contact_type_a_display'] = $values[$object->id]['contact_type_b_display'] = '';
            if (isset($values[$object->id]['contact_type_a'])) {
              $values[$object->id]['contact_type_a_display'] = $contactTypes[$values[$object->id]['contact_type_a']];
            }
            if (isset($values[$object->id]['contact_type_b'])) {
              $values[$object->id]['contact_type_b_display'] = $contactTypes[$values[$object->id]['contact_type_b']];
            }
          }

          // populate action links
          $this->action($object, $action, $values[$object->id], $links, $permission);

          if (isset($object->mapping_type_id)) {
            $mappintTypes = CRM_Core_DAO_Mapping::buildOptions('mapping_type_id');
            $values[$object->id]['mapping_type'] = $mappintTypes[$object->mapping_type_id];
          }
        }
      }
    }
    foreach ($this->getExpectedRowProperties() as $key) {
      foreach ($values as $index => $value) {
        if (!array_key_exists($key, $value)) {
          $values[$index][$key] = NULL;
        }
      }
    }
    return $values;
  }

  /**
   * Get any properties that should always be present in each row (null if no value).
   *
   * @return array
   */
  protected function getExpectedRowProperties(): array {
    return [];
  }

  /**
   * Get the menu path corresponding to an action on this entity
   *
   * @param string $linkAction
   *   e.g. "view"
   * @return string|null
   *   e.g. "civicrm/activity?reset=1&action=view&id=[id]"
   */
  public function getLinkPath(string $linkAction): ?string {
    return $this->getBAOName()::getEntityPaths()[$linkAction] ?? NULL;
  }

}
