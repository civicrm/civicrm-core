<?php

class CRM_Search_Page_Ang extends CRM_Core_Page {
  /**
   * @var string[]
   */
  private $loadOptions = ['id', 'name', 'label', 'description', 'color', 'icon'];

  /**
   * @var array
   */
  private $schema = [];

  /**
   * @var string[]
   */
  private $allowedEntities = [];

  public function run() {
    $breadCrumb = [
      'title' => ts('Search'),
      'url' => CRM_Utils_System::url('civicrm/search'),
    ];
    CRM_Utils_System::appendBreadCrumb([$breadCrumb]);

    $this->getSchema();

    // If user does not have permission to search any entity, bye bye.
    if (!$this->allowedEntities) {
      CRM_Utils_System::permissionDenied();
    }

    // Add client-side vars for the search UI
    $vars = [
      'operators' => CRM_Utils_Array::makeNonAssociative($this->getOperators()),
      'schema' => $this->schema,
      'links' => $this->getLinks(),
      'loadOptions' => $this->loadOptions,
      'actions' => $this->getActions(),
      'functions' => CRM_Api4_Page_Api4Explorer::getSqlFunctions(),
    ];

    Civi::resources()
      ->addPermissions(['edit groups', 'administer reserved groups'])
      ->addVars('search', $vars);

    // Load angular module
    $loader = new Civi\Angular\AngularLoader();
    $loader->setModules(['search']);
    $loader->setPageName('civicrm/search');
    $loader->useApp([
      'defaultRoute' => '/Contact',
    ]);
    $loader->load();
    parent::run();
  }

  /**
   * @return string[]
   */
  private function getOperators() {
    return [
      '=' => '=',
      '!=' => '≠',
      '>' => '>',
      '<' => '<',
      '>=' => '≥',
      '<=' => '≤',
      'CONTAINS' => ts('Contains'),
      'IN' => ts('Is In'),
      'NOT IN' => ts('Not In'),
      'LIKE' => ts('Is Like'),
      'NOT LIKE' => ts('Not Like'),
      'BETWEEN' => ts('Is Between'),
      'NOT BETWEEN' => ts('Not Between'),
      'IS NULL' => ts('Is Null'),
      'IS NOT NULL' => ts('Not Null'),
    ];
  }

  /**
   * Populates $this->schema & $this->allowedEntities
   */
  private function getSchema() {
    $schema = \Civi\Api4\Entity::get()
      ->addSelect('name', 'title', 'description', 'icon')
      ->addWhere('name', '!=', 'Entity')
      ->addOrderBy('title')
      ->setChain([
        'get' => ['$name', 'getActions', ['where' => [['name', '=', 'get']]], ['params']],
      ])->execute();
    $getFields = ['name', 'label', 'description', 'options', 'input_type', 'input_attrs', 'data_type', 'serialize'];
    foreach ($schema as $entity) {
      // Skip if entity doesn't have a 'get' action or the user doesn't have permission to use get
      if ($entity['get']) {
        // Get fields and pre-load options for certain prominent entities
        $loadOptions = in_array($entity['name'], ['Contact', 'Group']) ? $this->loadOptions : FALSE;
        if ($loadOptions) {
          $entity['optionsLoaded'] = TRUE;
        }
        $entity['fields'] = civicrm_api4($entity['name'], 'getFields', [
          'select' => $getFields,
          'where' => [['permission', 'IS NULL']],
          'orderBy' => ['label'],
          'loadOptions' => $loadOptions,
        ]);
        // Get the names of params this entity supports (minus some obvious ones)
        $params = $entity['get'][0];
        CRM_Utils_Array::remove($params, 'checkPermissions', 'debug', 'chain', 'language');
        unset($entity['get']);
        $this->schema[] = ['params' => array_keys($params)] + array_filter($entity);
        $this->allowedEntities[] = $entity['name'];
      }
    }
  }

  /**
   * @return array
   */
  private function getLinks() {
    $results = [];
    $keys = array_flip(['alias', 'entity', 'joinType']);
    foreach (civicrm_api4('Entity', 'getLinks', ['where' => [['entity', 'IN', $this->allowedEntities]]], ['entity' => 'links']) as $entity => $links) {
      $entityLinks = [];
      foreach ($links as $link) {
        if (!empty($link['entity']) && in_array($link['entity'], $this->allowedEntities)) {
          // Use entity.alias as array key to avoid duplicates
          $entityLinks[$link['entity'] . $link['alias']] = array_intersect_key($link, $keys);
        }
      }
      $results[$entity] = array_values($entityLinks);
    }
    return array_filter($results);
  }

  /**
   * @return array[]
   */
  private function getActions() {
    // Note: the placeholder %1 will be replaced with entity name on the clientside
    $actions = [
      'export' => [
        'title' => ts('Export %1'),
        'icon' => 'fa-file-excel-o',
        'entities' => array_keys(CRM_Export_BAO_Export::getComponents()),
        'crmPopup' => [
          'path' => "'civicrm/export/standalone'",
          'query' => "{entity: entity, id: ids.join(',')}",
        ],
      ],
      'update' => [
        'title' => ts('Update %1'),
        'icon' => 'fa-save',
        'entities' => [],
        'uiDialog' => ['templateUrl' => '~/search/crmSearchActions/crmSearchActionUpdate.html'],
      ],
      'delete' => [
        'title' => ts('Delete %1'),
        'icon' => 'fa-trash',
        'entities' => [],
        'uiDialog' => ['templateUrl' => '~/search/crmSearchActions/crmSearchActionDelete.html'],
      ],
    ];

    // Check permissions for update & delete actions
    foreach ($this->allowedEntities as $entity) {
      $result = civicrm_api4($entity, 'getActions', [
        'where' => [['name', 'IN', ['update', 'delete']]],
      ], ['name']);
      foreach ($result as $action) {
        // Contacts have their own delete action
        if (!($entity === 'Contact' && $action === 'delete')) {
          $actions[$action]['entities'][] = $entity;
        }
      }
    }

    // Add contact tasks which support standalone mode (with a 'url' property)
    $contactTasks = CRM_Contact_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission());
    foreach (CRM_Contact_Task::tasks() as $id => $task) {
      if (isset($contactTasks[$id]) && !empty($task['url'])) {
        $actions['contact.' . $id] = [
          'title' => $task['title'],
          'entities' => ['Contact'],
          'icon' => $task['icon'] ?? 'fa-gear',
          'crmPopup' => [
            'path' => "'{$task['url']}'",
            'query' => "{cids: ids.join(',')}",
          ],
        ];
      }
    }

    return $actions;
  }

}
