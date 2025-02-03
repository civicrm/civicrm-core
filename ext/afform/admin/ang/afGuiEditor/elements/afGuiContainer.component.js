// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiContainer', {
    templateUrl: '~/afGuiEditor/elements/afGuiContainer.html',
    bindings: {
      node: '<',
      join: '<',
      entityName: '<',
      deleteThis: '&'
    },
    require: {
      editor: '^^afGuiEditor',
      parentContainer: '?^^afGuiContainer'
    },
    controller: function($scope, $element, crmApi4, dialogService, afGui) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;
      var genericElements = [];

      this.$onInit = function() {
        if (ctrl.node['#tag'] && ((ctrl.node['#tag'] in afGui.meta.blocks) || ctrl.join)) {
          var blockNode = getBlockNode(),
            blockTag = blockNode ? blockNode['#tag'] : null;
          if (blockTag && (blockTag in afGui.meta.blocks) && !afGui.meta.blocks[blockTag].layout) {
            ctrl.loading = true;
            crmApi4('Afform', 'loadAdminData', {
              definition: {name: afGui.meta.blocks[blockTag].name},
              skipEntities: _.transform(afGui.meta.entities, function(result, entity, entityName) {
                if (entity.fields) {
                  result.push(entityName);
                }
              }, [])
            }, 0).then(function(data) {
              afGui.addMeta(data);
              initializeBlockContainer();
              ctrl.loading = false;
            });
          }
          initializeBlockContainer();
        }
        _.each(afGui.meta.elements, function(element) {
          if (element.directive) {
            genericElements.push(element.directive);
          }
        });
      };

      this.sortableOptions = {
        handle: '.af-gui-bar',
        connectWith: '[ui-sortable]',
        cancel: 'input,textarea,button,select,option,a,.dropdown-menu',
        placeholder: 'af-gui-dropzone',
        scrollSpeed: 8,
        containment: '#afGuiEditor-canvas-body',
        helper: function(e, $el) {
          // Prevent draggable item from being too large for the drop zones.
          return $el.clone().css({width: '50px', height: '20px'});
        }
      };

      $scope.isSelectedFieldset = function(entityName) {
        return entityName === ctrl.editor.getSelectedEntityName();
      };

      $scope.isSelectedSearchFieldset = function(node) {
        var key = $scope.getSearchKey(node);
        return key === ctrl.editor.getSelectedEntityName();
      };

      $scope.getSearchKey = function(node) {
        var searchDisplays = afGui.findRecursive(node['#children'], function(item) {
          return item['#tag'] && item['#tag'].indexOf('crm-search-display-') === 0 && item['search-name'];
        });
        if (searchDisplays && searchDisplays.length) {
          return searchDisplays[0]['search-name'] + (searchDisplays[0]['display-name'] ? '.' + searchDisplays[0]['display-name'] : '');
        }
      };

      // Finds a SearchDisplay within this container or within the fieldset containing this container
      this.getSearchDisplay = function() {
        var searchKey = ctrl.getDataEntity();
        if (searchKey && !ctrl.entityName) {
          return afGui.getSearchDisplay.apply(null, searchKey.split('.'));
        }
      };

      $scope.selectEntity = function() {
        if (ctrl.node['af-fieldset']) {
          ctrl.editor.selectEntity(ctrl.node['af-fieldset']);
        } else if ('af-fieldset' in ctrl.node) {
          var searchKey = $scope.getSearchKey(ctrl.node);
          if (searchKey) {
            ctrl.editor.selectEntity(searchKey);
          }
        }
      };

      $scope.tags = {
        div: ts('Container'),
        fieldset: ts('Fieldset'),
        details: ts('Collapsible')
      };

      // Block settings
      var block = {};
      $scope.block = null;

      this.isBlock = function() {
        return 'layout' in block;
      };

      this.isJoin = function() {
        return !!ctrl.join;
      };

      $scope.getSetChildren = function(val) {
        var collection = block.layout || (ctrl.node && ctrl.node['#children']);
        return arguments.length ? (collection = val) : collection;
      };

      $scope.isRepeatable = function() {
        return (ctrl.join && $scope.getRepeatMax() !== 1) ||
          (block.directive && afGui.meta.blocks[block.directive].repeat) ||
          (ctrl.node['af-fieldset'] && ctrl.editor.getEntityDefn(ctrl.editor.getEntity(ctrl.node['af-fieldset'])) !== false);
      };

      this.toggleRepeat = function() {
        if ('af-repeat' in ctrl.node) {
          delete ctrl.node.max;
          delete ctrl.node.min;
          delete ctrl.node['af-repeat'];
          delete ctrl.node['add-icon'];
          delete ctrl.node['af-copy'];
          delete ctrl.node['copy-icon'];

        } else {
          ctrl.node.min = '1';
          ctrl.node['af-repeat'] = ts('Add');
          ctrl.node['af-copy'] = ts('Copy');
          delete ctrl.node.data;
        }
      };

      this.getCollapsibleIcon = function() {
        if (ctrl.node['#tag'] === 'details') {
          return 'open' in ctrl.node ? 'fa-caret-down' : 'fa-caret-right';
        }
        return '';
      };

      // Sets min value for af-repeat as a string, returns it as an int
      $scope.getSetMin = function(val) {
        if (arguments.length) {
          if (ctrl.node.max && val > parseInt(ctrl.node.max, 10)) {
            ctrl.node.max = '' + val;
          }
          if (!val) {
            delete ctrl.node.min;
          }
          else {
            ctrl.node.min = '' + val;
          }
        }
        return ctrl.node.min ? parseInt(ctrl.node.min, 10) : null;
      };

      // Sets max value for af-repeat as a string, returns it as an int
      $scope.getSetMax = function(val) {
        if (arguments.length) {
          if (ctrl.node.min && val && val < parseInt(ctrl.node.min, 10)) {
            ctrl.node.min = '' + val;
          }
          if (typeof val !== 'number') {
            delete ctrl.node.max;
          }
          else {
            ctrl.node.max = '' + val;
          }
        }
        return ctrl.node.max ? parseInt(ctrl.node.max, 10) : null;
      };

      // Returns the maximum number of repeats allowed if this is a joined entity with a limit
      // Value comes from civicrm_custom_group.max_multiple for custom entities,
      // or from afformEntity php file for core entities.
      $scope.getRepeatMax = function() {
        if (ctrl.join) {
          return ctrl.getJoinEntity().repeat_max || '';
        }
        return '';
      };

      $scope.pickAddIcon = function() {
        afGui.pickIcon().then(function(val) {
          ctrl.node['add-icon'] = val;
        });
      };

      $scope.pickCopyIcon = function() {
        afGui.pickIcon().then(function(val) {
          ctrl.node['copy-icon'] = val;
        });
      };

      function getBlockNode() {
        return !ctrl.join ? ctrl.node : (ctrl.node['#children'] && ctrl.node['#children'].length === 1 ? ctrl.node['#children'][0] : null);
      }

      function setBlockDirective(directive) {
        if (ctrl.join) {
          ctrl.node['#children'] = [{'#tag': directive}];
        } else {
          delete ctrl.node['#children'];
          delete ctrl.node['class'];
          ctrl.node['#tag'] = directive;
        }
      }

      function overrideBlockContents(layout) {
        ctrl.node['#children'] = layout || [];
        if (!ctrl.join) {
          ctrl.node['#tag'] = 'div';
          ctrl.node['class'] = 'af-container';
        }
        block.layout = block.directive = null;
      }

      $scope.layouts = {
        'af-layout-rows': ts('Contents display as rows'),
        'af-layout-cols': ts('Contents are evenly-spaced columns'),
        'af-layout-inline': ts('Contents are arranged inline')
      };

      $scope.getLayout = function() {
        if (!ctrl.node) {
          return '';
        }
        return _.intersection(afGui.splitClass(ctrl.node['class']), _.keys($scope.layouts))[0] || 'af-layout-rows';
      };

      $scope.setLayout = function(val) {
        var classes = ['af-container'];
        if (val !== 'af-layout-rows') {
          classes.push(val);
        }
        afGui.modifyClasses(ctrl.node, _.keys($scope.layouts), classes);
      };

      $scope.selectBlockDirective = function() {
        if (block.directive) {
          block.layout = _.cloneDeep(afGui.meta.blocks[block.directive].layout);
          block.original = block.directive;
          setBlockDirective(block.directive);
        }
        else {
          overrideBlockContents(block.layout);
        }
      };

      this.onChangeUpdateAction = function() {
        if (!ctrl.node.actions.update) {
          ctrl.node.actions.delete = false;
        }
      };

      function initializeBlockContainer() {
        // Set defaults for 'actions'
        if (!('actions' in ctrl.node)) {
          ctrl.node.actions = {update: true, delete: true};
        }

        // Cancel the below $watch expressions if already set
        _.each(block.listeners, function(deregister) {
          deregister();
        });

        block = $scope.block = {
          directive: null,
          layout: null,
          original: null,
          options: [],
          listeners: []
        };

        _.each(afGui.meta.blocks, function(blockInfo, directive) {
          if (directive === ctrl.node['#tag'] || (blockInfo.join_entity && blockInfo.join_entity === ctrl.getFieldEntityType())) {
            block.options.push({
              id: directive,
              text: blockInfo.title
            });
          }
        });

        if (getBlockNode() && getBlockNode()['#tag'] in afGui.meta.blocks) {
          block.directive = block.original = getBlockNode()['#tag'];
          block.layout = _.cloneDeep(afGui.meta.blocks[block.directive].layout);
        }

        block.listeners.push($scope.$watch('block.layout', function (layout, oldVal) {
          if (block.directive && layout && layout !== oldVal && !angular.equals(layout, afGui.meta.blocks[block.directive].layout)) {
            overrideBlockContents(block.layout);
          }
        }, true));
      }

      this.canSaveAsBlock = function() {
        return !ctrl.node['af-fieldset'] &&
          // Exclude blocks
          !ctrl.isBlock() &&
          // Exclude the child of a block
          (!ctrl.parentContainer || !ctrl.parentContainer.isBlock()) &&
          // Excludes search display containers and their children
          (ctrl.entityName || '') === ctrl.getDataEntity();
      };

      $scope.saveBlock = function() {
        var options = CRM.utils.adjustDialogDefaults({
          width: '500px',
          height: '300px',
          autoOpen: false,
          title: ts('Save block')
        });
        var model = {
          title: '',
          name: null,
          type: 'block',
          layout: ctrl.node['#children']
        };
        if (ctrl.join) {
          model.join_entity = ctrl.join;
        }
        if ($scope.block && $scope.block.original) {
          model.title = afGui.meta.blocks[$scope.block.original].title;
          model.name = afGui.meta.blocks[$scope.block.original].name;
        }
        else {
          model.entity_type = ctrl.getFieldEntityType();
        }
        dialogService.open('saveBlockDialog', '~/afGuiEditor/saveBlock.html', model, options)
          .then(function(block) {
            afGui.meta.blocks[block.directive_name] = block;
            setBlockDirective(block.directive_name);
            initializeBlockContainer();
          });
      };

      this.node = ctrl.node;

      this.getNodeType = function(node) {
        if (!node || !node['#tag']) {
          return null;
        }
        if (node['#tag'] === 'af-field') {
          return 'field';
        }
        if (node['af-fieldset']) {
          return 'fieldset';
        }
        else if ('af-fieldset' in node) {
          return 'searchFieldset';
        }
        if (node['af-join']) {
          return 'join';
        }
        if (node['#tag'] === 'af-tabset') {
          return 'tabset';
        }
        if (node['#tag'] && node['#tag'] in afGui.meta.blocks) {
          return 'container';
        }
        if (node['#tag'] && (node['#tag'].slice(0, 19) === 'crm-search-display-')) {
          return 'searchDisplay';
        }
        if (node['#tag'] && _.includes(genericElements, node['#tag'])) {
          return 'generic';
        }
        var classes = afGui.splitClass(node['class']),
          types = ['af-container', 'af-text', 'af-button', 'af-markup'],
          type = _.intersection(types, classes);
        return type.length ? type[0].replace('af-', '') : null;
      };

      this.getSetTitle = function(value) {
        if (arguments.length) {
          if (value.length) {
            ctrl.node['af-title'] = value;
          } else {
            delete ctrl.node['af-title'];
          }
        }
        return ctrl.node['af-title'];
      };

      this.getToolTip = function() {
        var text = '', nodeType;
        if (!$scope.block) {
          nodeType = ctrl.getNodeType(ctrl.node);
          if (nodeType === 'fieldset') {
            text = ctrl.editor.getEntity(ctrl.entityName).label;
          } else if (nodeType === 'searchFieldset') {
            text = ts('Search Display');
          }
          text += ' ' + $scope.tags[ctrl.node['#tag']];
        }
        return text;
      };

      this.removeElement = function(element) {
        afGui.removeRecursive($scope.getSetChildren(), {$$hashKey: element.$$hashKey});
        ctrl.editor.onRemoveElement();
      };

      this.removeField = function(fieldName) {
        afGui.removeRecursive($scope.getSetChildren(), {'#tag': 'af-field', name: fieldName});
      };

      this.getEntityName = function() {
        return ctrl.entityName ? ctrl.entityName.split('-join-')[0] : null;
      };

      this.getDataEntity = function() {
        return $element.attr('data-entity') || '';
      };

      this.getJoinEntity = function() {
        if (!ctrl.join) {
          return null;
        }
        return afGui.getEntity(ctrl.join);
      };

      // Returns the primary entity type for this container e.g. "Contact"
      this.getMainEntityType = function() {
        return ctrl.editor && ctrl.editor.getEntity(ctrl.getEntityName()).type;
      };

      // Returns the entity type for fields within this conainer (join entity type if this is a join, else the primary entity type)
      this.getFieldEntityType = function(fieldKey) {
        var entityType;
        // If entityName is declared for this fieldset, return entity-type or join-type
        if (ctrl.entityName) {
          var joinType = ctrl.entityName.split('-join-');
          entityType = joinType[1] || (ctrl.editor && ctrl.editor.getEntity(joinType[0]).type);
        } else {
          var searchDisplay = ctrl.getSearchDisplay(),
            fieldName = fieldKey.substr(fieldKey.indexOf('.') + 1),
            prefix = _.includes(fieldKey, '.') ? fieldKey.split('.')[0] : null;
          if (prefix) {
            _.each(searchDisplay['saved_search_id.api_params'].join, function(join) {
              var joinInfo = join[0].split(' AS ');
              if (prefix === joinInfo[1]) {
                entityType = joinInfo[0];
                // If entity doesn't contain field, it belongs to the bridge join
                if (!(fieldName in afGui.getEntity(entityType).fields)) {
                  entityType = join[2];
                }
                return false;
              }
            });
          }
          if (!entityType) {
            entityType = searchDisplay['saved_search_id.api_entity'];
          }
        }

        return entityType;
      };

    }
  });

})(angular, CRM.$, CRM._);
