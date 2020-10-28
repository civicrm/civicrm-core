(function(angular, $, _) {
  "use strict";
  angular.module('afGuiEditor', CRM.angRequires('afGuiEditor'));

  angular.module('afGuiEditor').directive('afGuiEditor', function(crmApi4, $parse, $timeout, $location) {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/main.html',
      scope: {
        afGuiEditor: '='
      },
      link: function($scope, element, attrs) {
        // Shoehorn in a non-angular widget for picking icons
        CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmIconPicker.js').done(function() {
          $('#af-gui-icon-picker').crmIconPicker().change(function() {
            if (editingIcon) {
              $scope.$apply(function() {
                editingIcon[editingIconProp] = $('#af-gui-icon-picker').val();
                editingIcon = null;
                $('#af-gui-icon-picker').val('').change();
              });
            }
          });
        });
      },
      controller: function($scope) {
        var ts = $scope.ts = CRM.ts();
        $scope.afform = null;
        $scope.saving = false;
        $scope.selectedEntityName = null;
        $scope.meta = this.meta = CRM.afformAdminData;
        this.scope = $scope;
        var editor = $scope.editor = this;
        var newForm = {
          title: '',
          permission: 'access CiviCRM',
          layout: [{
            '#tag': 'af-form',
            ctrl: 'afform',
            '#children': []
          }]
        };
        // Fetch the current form plus all blocks
        crmApi4('Afform', 'get', {where: [["OR", [["name", "=", $scope.afGuiEditor.name], ["block", "IS NOT NULL"]]]], layoutFormat: 'shallow', formatWhitespace: true})
          .then(initialize);

        // Initialize the current form + list of blocks
        function initialize(afforms) {
          $scope.meta.blocks = {};
          _.each(afforms, function(form) {
            evaluate(form.layout);
            if (form.block) {
              $scope.meta.blocks[form.directive_name] = form;
            }
            if (form.name === $scope.afGuiEditor.name) {
              $scope.afform = form;
            }
          });
          if (!$scope.afform) {
            $scope.afform = _.cloneDeep(newForm);
            if ($scope.afGuiEditor.name != '0') {
              alert('Error: unknown form "' + $scope.afGuiEditor.name + '"');
            }
          }
          $scope.layout = findRecursive($scope.afform.layout, {'#tag': 'af-form'})[0];
          $scope.entities = findRecursive($scope.layout['#children'], {'#tag': 'af-entity'}, 'name');

          if ($scope.afGuiEditor.name == '0') {
            editor.addEntity('Individual');
            $scope.layout['#children'].push($scope.meta.elements.submit.element);
          }

          // Set changesSaved to true on initial load, false thereafter whenever changes are made to the model
          $scope.changesSaved = $scope.afGuiEditor.name == '0' ? false : 1;
          $scope.$watch('afform', function () {
            $scope.changesSaved = $scope.changesSaved === 1;
          }, true);
        }

        this.addEntity = function(type) {
          var meta = editor.meta.entities[type],
            num = 1;
          // Give this new entity a unique name
          while (!!$scope.entities[type + num]) {
            num++;
          }
          $scope.entities[type + num] = _.assign($parse(meta.defaults)($scope), {
            '#tag': 'af-entity',
            type: meta.entity,
            name: type + num,
            label: meta.label + ' ' + num
          });
          // Add this af-entity tag after the last existing one
          var pos = 1 + _.findLastIndex($scope.layout['#children'], {'#tag': 'af-entity'});
          $scope.layout['#children'].splice(pos, 0, $scope.entities[type + num]);
          // Create a new af-fieldset container for the entity
          var fieldset = _.cloneDeep(editor.meta.elements.fieldset.element);
          fieldset['af-fieldset'] = type + num;
          fieldset['#children'][0]['#children'][0]['#text'] = meta.label + ' ' + num;
          // Add default contact name block
          if (meta.entity === 'Contact') {
            fieldset['#children'].push({'#tag': 'afblock-name-' + type.toLowerCase()});
          }
          // Attempt to place the new af-fieldset after the last one on the form
          pos = 1 + _.findLastIndex($scope.layout['#children'], 'af-fieldset');
          if (pos) {
            $scope.layout['#children'].splice(pos, 0, fieldset);
          } else {
            $scope.layout['#children'].push(fieldset);
          }
          return type + num;
        };

        this.removeEntity = function(entityName) {
          delete $scope.entities[entityName];
          removeRecursive($scope.layout['#children'], {'#tag': 'af-entity', name: entityName});
          removeRecursive($scope.layout['#children'], {'af-fieldset': entityName});
          this.selectEntity(null);
        };

        this.selectEntity = function(entityName) {
          $scope.selectedEntityName = entityName;
        };

        this.getField = function(entityType, fieldName) {
          return $scope.meta.entities[entityType].fields[fieldName];
        };

        this.getEntity = function(entityName) {
          return $scope.entities[entityName];
        };

        this.getSelectedEntityName = function() {
          return $scope.selectedEntityName;
        };

        // Validates that a drag-n-drop action is allowed
        this.onDrop = function(event, ui) {
          var sort = ui.item.sortable;
          // Check if this is a callback for an item dropped into a different container
          // @see https://github.com/angular-ui/ui-sortable notes on canceling
          if (!sort.received && sort.source[0] !== sort.droptarget[0]) {
            var $source = $(sort.source[0]),
              $target = $(sort.droptarget[0]),
              $item = $(ui.item[0]);
            // Fields cannot be dropped outside their own entity
            if ($item.is('[af-gui-field]') || $item.has('[af-gui-field]').length) {
              if ($source.closest('[data-entity]').attr('data-entity') !== $target.closest('[data-entity]').attr('data-entity')) {
                return sort.cancel();
              }
            }
            // Entity-fieldsets cannot be dropped into other entity-fieldsets
            if ((sort.model['af-fieldset'] || $item.has('.af-gui-fieldset').length) && $target.closest('.af-gui-fieldset').length) {
              return sort.cancel();
            }
          }
        };

        $scope.addEntity = function(entityType) {
          var entityName = editor.addEntity(entityType);
          editor.selectEntity(entityName);
        };

        $scope.save = function() {
          $scope.saving = $scope.changesSaved = true;
          crmApi4('Afform', 'save', {formatWhitespace: true, records: [JSON.parse(angular.toJson($scope.afform))]})
            .then(function (data) {
              $scope.saving = false;
              $scope.afform.name = data[0].name;
              // FIXME: This causes an unnecessary reload when saving a new form
              $location.search('name', data[0].name);
            });
        };

        $scope.$watch('afform.title', function(newTitle, oldTitle) {
          if (typeof oldTitle === 'string') {
            _.each($scope.entities, function(entity) {
              if (entity.data && entity.data.source === oldTitle) {
                entity.data.source = newTitle;
              }
            });
          }
        });

        // Parse strings of javascript that php couldn't interpret
        function evaluate(collection) {
          _.each(collection, function(item) {
            if (_.isPlainObject(item)) {
              evaluate(item['#children']);
              _.each(item, function(node, idx) {
                if (_.isString(node)) {
                  var str = _.trim(node);
                  if (str[0] === '{' || str[0] === '[' || str.slice(0, 3) === 'ts(') {
                    item[idx] = $parse(str)({ts: $scope.ts});
                  }
                }
              });
            }
          });
        }

      }
    };
  });

  // Recursively searches a collection and its children using _.filter
  // Returns an array of all matches, or an object if the indexBy param is used
  function findRecursive(collection, predicate, indexBy) {
    var items = _.filter(collection, predicate);
    _.each(collection, function(item) {
      if (_.isPlainObject(item) && item['#children']) {
        var childMatches = findRecursive(item['#children'], predicate);
        if (childMatches.length) {
          Array.prototype.push.apply(items, childMatches);
        }
      }
    });
    return indexBy ? _.indexBy(items, indexBy) : items;
  }

  // Applies _.remove() to an item and its children
  function removeRecursive(collection, removeParams) {
    _.remove(collection, removeParams);
    _.each(collection, function(item) {
      if (_.isPlainObject(item) && item['#children']) {
        removeRecursive(item['#children'], removeParams);
      }
    });
  }

  // Turns a space-separated list (e.g. css classes) into an array
  function splitClass(str) {
    if (_.isArray(str)) {
      return str;
    }
    return str ? _.unique(_.trim(str).split(/\s+/g)) : [];
  }

  angular.module('afGuiEditor').directive('afGuiEntity', function($timeout) {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/entity.html',
      scope: {
        entity: '=afGuiEntity'
      },
      require: '^^afGuiEditor',
      link: function ($scope, element, attrs, editor) {
        $scope.editor = editor;
      },
      controller: function ($scope) {
        var ts = $scope.ts = CRM.ts();
        $scope.controls = {};
        $scope.fieldList = [];
        $scope.blockList = [];
        $scope.blockTitles = [];
        $scope.elementList = [];
        $scope.elementTitles = [];

        function getEntityType() {
          return $scope.entity.type === 'Contact' ? $scope.entity.data.contact_type : $scope.entity.type;
        }

        $scope.getMeta = function() {
          return $scope.editor ? $scope.editor.meta.entities[getEntityType()] : {};
        };

        $scope.valuesFields = function() {
          var fields = _.transform($scope.getMeta().fields, function(fields, field) {
            fields.push({id: field.name, text: field.title, disabled: $scope.fieldInUse(field.name)});
          }, []);
          return {results: fields};
        };

        $scope.removeValue = function(entity, fieldName) {
          delete entity.data[fieldName];
        };

        function buildPaletteLists() {
          var search = $scope.controls.fieldSearch ? $scope.controls.fieldSearch.toLowerCase() : null;
          buildFieldList(search);
          buildBlockList(search);
          buildElementList(search);
        }

        function buildFieldList(search) {
          $scope.fieldList.length = 0;
          $scope.fieldList.push({
            entityName: $scope.entity.name,
            entityType: getEntityType(),
            label: ts('%1 Fields', {1: $scope.getMeta().label}),
            fields: filterFields($scope.getMeta().fields)
          });

          _.each($scope.editor.meta.entities, function(entity, entityName) {
            if (check($scope.editor.scope.layout['#children'], {'af-join': entityName})) {
              $scope.fieldList.push({
                entityName: $scope.entity.name + '-join-' + entityName,
                entityType: entityName,
                label: ts('%1 Fields', {1: entity.label}),
                fields: filterFields(entity.fields)
              });
            }
          });

          function filterFields(fields) {
            return _.transform(fields, function(fieldList, field) {
              if (!search || _.contains(field.name, search) || _.contains(field.title.toLowerCase(), search)) {
                fieldList.push({
                  "#tag": "af-field",
                  name: field.name
                });
              }
            }, []);
          }
        }

        function buildBlockList(search) {
          $scope.blockList.length = 0;
          $scope.blockTitles.length = 0;
          _.each($scope.editor.meta.blocks, function(block, directive) {
            if ((!search || _.contains(directive, search) || _.contains(block.name.toLowerCase(), search) || _.contains(block.title.toLowerCase(), search)) &&
              (block.block === '*' || block.block === $scope.entity.type || ($scope.entity.type === 'Contact' && block.block === $scope.entity.data.contact_type))
            ) {
              var item = {"#tag": block.join ? "div" : directive};
              if (block.join) {
                item['af-join'] = block.join;
                item['#children'] = [{"#tag": directive}];
              }
              if (block.repeat) {
                item['af-repeat'] = ts('Add');
                item.min = '1';
                if (typeof block.repeat === 'number') {
                  item.max = '' + block.repeat;
                }
              }
              $scope.blockList.push(item);
              $scope.blockTitles.push(block.title);
            }
          });
        }

        function buildElementList(search) {
          $scope.elementList.length = 0;
          $scope.elementTitles.length = 0;
          _.each($scope.editor.meta.elements, function(element, name) {
            if (!search || _.contains(name, search) || _.contains(element.title.toLowerCase(), search)) {
              var node = _.cloneDeep(element.element);
              if (name === 'fieldset') {
                node['af-fieldset'] = $scope.entity.name;
              }
              $scope.elementList.push(node);
              $scope.elementTitles.push(name === 'fieldset' ? ts('Fieldset for %1', {1: $scope.entity.label}) : element.title);
            }
          });
        }

        $scope.clearSearch = function() {
          $scope.controls.fieldSearch = '';
        };

        // This gets called from jquery-ui so we have to manually apply changes to scope
        $scope.buildPaletteLists = function() {
          $timeout(function() {
            $scope.$apply(function() {
              buildPaletteLists();
            });
          });
        };

        // Checks if a field is on the form or set as a value
        $scope.fieldInUse = function(fieldName) {
          var data = $scope.entity.data || {};
          if (fieldName in data) {
            return true;
          }
          return check($scope.editor.scope.layout['#children'], {'#tag': 'af-field', name: fieldName});
        };

        $scope.blockInUse = function(block) {
          if (block['af-join']) {
            return check($scope.editor.scope.layout['#children'], {'af-join': block['af-join']});
          }
          var fieldsInBlock = _.pluck(findRecursive($scope.editor.meta.blocks[block['#tag']].layout, {'#tag': 'af-field'}), 'name');
          return check($scope.editor.scope.layout['#children'], function(item) {
            return item['#tag'] === 'af-field' && _.includes(fieldsInBlock, item.name);
          });
        };

        // Check for a matching item for this entity
        // Recursively checks the form layout, including block directives
        function check(group, criteria, found) {
          if (!found) {
            found = {};
          }
          if (_.find(group, criteria)) {
            found.match = true;
            return true;
          }
          _.each(group, function(item) {
            if (found.match) {
              return false;
            }
            if (_.isPlainObject(item)) {
              // Recurse through everything but skip fieldsets for other entities
              if ((!item['af-fieldset'] || (item['af-fieldset'] === $scope.entity.name)) && item['#children']) {
                check(item['#children'], criteria, found);
              }
              // Recurse into block directives
              else if (item['#tag'] && item['#tag'] in $scope.editor.meta.blocks) {
                check($scope.editor.meta.blocks[item['#tag']].layout, criteria, found);
              }
            }
          });
          return found.match;
        }

        $scope.$watch('controls.addValue', function(fieldName) {
          if (fieldName) {
            if (!$scope.entity.data) {
              $scope.entity.data = {};
            }
            $scope.entity.data[fieldName] = '';
            $scope.controls.addValue = '';
          }
        });

        $scope.$watch('controls.fieldSearch', buildPaletteLists);
      }
    };
  });

  angular.module('afGuiEditor').directive('afGuiContainer', function(crmApi4, dialogService) {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/container.html',
      scope: {
        node: '=afGuiContainer',
        join: '=',
        entityName: '='
      },
      require: ['^^afGuiEditor', '?^^afGuiContainer'],
      link: function($scope, element, attrs, ctrls) {
        var ts = $scope.ts = CRM.ts();
        $scope.editor = ctrls[0];
        $scope.parentContainer = ctrls[1];

        $scope.isSelectedFieldset = function(entityName) {
          return entityName === $scope.editor.getSelectedEntityName();
        };

        $scope.selectEntity = function() {
          if ($scope.node['af-fieldset']) {
            $scope.editor.selectEntity($scope.node['af-fieldset']);
          }
        };

        $scope.tags = {
          div: ts('Container'),
          fieldset: ts('Fieldset')
        };

        // Block settings
        var block = {};
        $scope.block = null;

        $scope.getSetChildren = function(val) {
          var collection = block.layout || ($scope.node && $scope.node['#children']);
          return arguments.length ? (collection = val) : collection;
        };

        $scope.isRepeatable = function() {
          return $scope.node['af-fieldset'] || (block.directive && $scope.editor.meta.blocks[block.directive].repeat) || $scope.join;
        };

        $scope.toggleRepeat = function() {
          if ('af-repeat' in $scope.node) {
            delete $scope.node.max;
            delete $scope.node.min;
            delete $scope.node['af-repeat'];
            delete $scope.node['add-icon'];
          } else {
            $scope.node.min = '1';
            $scope.node['af-repeat'] = ts('Add');
          }
        };

        $scope.getSetMin = function(val) {
          if (arguments.length) {
            if ($scope.node.max && val > parseInt($scope.node.max, 10)) {
              $scope.node.max = '' + val;
            }
            if (!val) {
              delete $scope.node.min;
            }
            else {
              $scope.node.min = '' + val;
            }
          }
          return $scope.node.min ? parseInt($scope.node.min, 10) : null;
        };

        $scope.getSetMax = function(val) {
          if (arguments.length) {
            if ($scope.node.min && val && val < parseInt($scope.node.min, 10)) {
              $scope.node.min = '' + val;
            }
            if (typeof val !== 'number') {
              delete $scope.node.max;
            }
            else {
              $scope.node.max = '' + val;
            }
          }
          return $scope.node.max ? parseInt($scope.node.max, 10) : null;
        };

        $scope.pickAddIcon = function() {
          openIconPicker($scope.node, 'add-icon');
        };

        function getBlockNode() {
          return !$scope.join ? $scope.node : ($scope.node['#children'] && $scope.node['#children'].length === 1 ? $scope.node['#children'][0] : null);
        }

        function setBlockDirective(directive) {
          if ($scope.join) {
            $scope.node['#children'] = [{'#tag': directive}];
          } else {
            delete $scope.node['#children'];
            delete $scope.node['class'];
            $scope.node['#tag'] = directive;
          }
        }

        function overrideBlockContents(layout) {
          $scope.node['#children'] = layout || [];
          if (!$scope.join) {
            $scope.node['#tag'] = 'div';
            $scope.node['class'] = 'af-container';
          }
          block.layout = block.directive = null;
        }

        $scope.layouts = {
          'af-layout-rows': ts('Contents display as rows'),
          'af-layout-cols': ts('Contents are evenly-spaced columns'),
          'af-layout-inline': ts('Contents are arranged inline')
        };

        $scope.getLayout = function() {
          if (!$scope.node) {
            return '';
          }
          return _.intersection(splitClass($scope.node['class']), _.keys($scope.layouts))[0] || 'af-layout-rows';
        };

        $scope.setLayout = function(val) {
          var classes = ['af-container'];
          if (val !== 'af-layout-rows') {
            classes.push(val);
          }
          modifyClasses($scope.node, _.keys($scope.layouts), classes);
        };

        $scope.selectBlockDirective = function() {
          if (block.directive) {
            block.layout = _.cloneDeep($scope.editor.meta.blocks[block.directive].layout);
            block.original = block.directive;
            setBlockDirective(block.directive);
          }
          else {
            overrideBlockContents(block.layout);
          }
        };

        if (($scope.node['#tag'] in $scope.editor.meta.blocks) || $scope.join) {
          initializeBlockContainer();
        }

        function initializeBlockContainer() {

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

          _.each($scope.editor.meta.blocks, function(blockInfo, directive) {
            if (directive === $scope.node['#tag'] || blockInfo.join === $scope.container.getFieldEntityType()) {
              block.options.push({
                id: directive,
                text: blockInfo.title
              });
            }
          });

          if (getBlockNode() && getBlockNode()['#tag'] in $scope.editor.meta.blocks) {
            block.directive = block.original = getBlockNode()['#tag'];
            block.layout = _.cloneDeep($scope.editor.meta.blocks[block.directive].layout);
          }

          block.listeners.push($scope.$watch('block.layout', function (layout, oldVal) {
            if (block.directive && layout && layout !== oldVal && !angular.equals(layout, $scope.editor.meta.blocks[block.directive].layout)) {
              overrideBlockContents(block.layout);
            }
          }, true));
        }

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
            layout: $scope.node['#children']
          };
          if ($scope.join) {
            model.join = $scope.join;
          }
          if ($scope.block && $scope.block.original) {
            model.title = $scope.editor.meta.blocks[$scope.block.original].title;
            model.name = $scope.editor.meta.blocks[$scope.block.original].name;
            model.block = $scope.editor.meta.blocks[$scope.block.original].block;
          }
          else {
            model.block = $scope.container.getFieldEntityType() || '*';
          }
          dialogService.open('saveBlockDialog', '~/afGuiEditor/saveBlock.html', model, options)
            .then(function(block) {
              $scope.editor.meta.blocks[block.directive_name] = block;
              setBlockDirective(block.directive_name);
              initializeBlockContainer();
            });
        };

      },
      controller: function($scope) {
        var container = $scope.container = this;
        this.node = $scope.node;

        this.getNodeType = function(node) {
          if (!node) {
            return null;
          }
          if (node['#tag'] === 'af-field') {
            return 'field';
          }
          if (node['af-fieldset']) {
            return 'fieldset';
          }
          if (node['af-join']) {
            return 'join';
          }
          if (node['#tag'] && node['#tag'] in $scope.editor.meta.blocks) {
            return 'container';
          }
          var classes = splitClass(node['class']),
            types = ['af-container', 'af-text', 'af-button', 'af-markup'],
            type = _.intersection(types, classes);
          return type.length ? type[0].replace('af-', '') : null;
        };

        this.removeElement = function(element) {
          removeRecursive($scope.getSetChildren(), {$$hashKey: element.$$hashKey});
        };

        this.getEntityName = function() {
          return $scope.entityName.split('-join-')[0];
        };

        // Returns the primary entity type for this container e.g. "Contact"
        this.getMainEntityType = function() {
          return $scope.editor && $scope.editor.getEntity(container.getEntityName()).type;
        };

        // Returns the entity type for fields within this conainer (join entity type if this is a join, else the primary entity type)
        this.getFieldEntityType = function() {
          var joinType = $scope.entityName.split('-join-');
          return joinType[1] || ($scope.editor && $scope.editor.getEntity(joinType[0]).type);
        };

      }
    };
  });

  angular.module('afGuiEditor').controller('afGuiSaveBlock', function($scope, crmApi4, dialogService) {
    var ts = $scope.ts = CRM.ts(),
      model = $scope.model,
      original = $scope.original = {
        title: model.title,
        name: model.name
      };
    if (model.name) {
      $scope.$watch('model.name', function(val, oldVal) {
        if (!val && model.title === original.title) {
          model.title += ' ' + ts('(copy)');
        }
        else if (val === original.name && val !== oldVal) {
          model.title = original.title;
        }
      });
    }
    $scope.cancel = function() {
      dialogService.cancel('saveBlockDialog');
    };
    $scope.save = function() {
      $('.ui-dialog:visible').block();
      crmApi4('Afform', 'save', {formatWhitespace: true, records: [JSON.parse(angular.toJson(model))]})
        .then(function(result) {
          dialogService.close('saveBlockDialog', result[0]);
        });
    };
  });

  angular.module('afGuiEditor').directive('afGuiField', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/field.html',
      scope: {
        node: '=afGuiField'
      },
      require: ['^^afGuiEditor', '^^afGuiContainer'],
      link: function($scope, element, attrs, ctrls) {
        $scope.editor = ctrls[0];
        $scope.container = ctrls[1];
      },
      controller: function($scope) {
        var ts = $scope.ts = CRM.ts();
        $scope.editingOptions = false;
        var yesNo = [
          {key: '1', label: ts('Yes')},
          {key: '0', label: ts('No')}
        ];

        $scope.getEntity = function() {
          return $scope.editor ? $scope.editor.getEntity($scope.container.getEntityName()) : {};
        };

        $scope.getDefn = this.getDefn = function() {
          return $scope.editor ? $scope.editor.getField($scope.container.getFieldEntityType(), $scope.node.name) : {};
        };

        $scope.hasOptions = function() {
          var inputType = $scope.getProp('input_type');
          return _.contains(['CheckBox', 'Radio', 'Select'], inputType) && !(inputType === 'CheckBox' && !$scope.getDefn().options);
        };

        $scope.getOptions = this.getOptions = function() {
          if ($scope.node.defn && $scope.node.defn.options) {
            return $scope.node.defn.options;
          }
          return $scope.getDefn().options || ($scope.getProp('input_type') === 'CheckBox' ? null : yesNo);
        };

        $scope.resetOptions = function() {
          delete $scope.node.defn.options;
        };

        $scope.editOptions = function() {
          $scope.editingOptions = true;
          $('#afGuiEditor').addClass('af-gui-editing-content');
        };

        $scope.inputTypeCanBe = function(type) {
          var defn = $scope.getDefn();
          switch (type) {
            case 'CheckBox':
            case 'Radio':
            case 'Select':
              return !(!defn.options && defn.data_type !== 'Boolean');

            case 'TextArea':
            case 'RichTextEditor':
              return (defn.data_type === 'Text' || defn.data_type === 'String');
          }
          return true;
        };

        // Returns a value from either the local field defn or the base defn
        $scope.getProp = function(propName) {
          var path = propName.split('.'),
            item = path.pop(),
            localDefn = drillDown($scope.node.defn || {}, path);
          if (typeof localDefn[item] !== 'undefined') {
            return localDefn[item];
          }
          return drillDown($scope.getDefn(), path)[item];
        };

        // Checks for a value in either the local field defn or the base defn
        $scope.propIsset = function(propName) {
          var val = $scope.getProp(propName);
          return !(typeof val === 'undefined' || val === null);
        };

        $scope.toggleLabel = function() {
          $scope.node.defn = $scope.node.defn || {};
          if ($scope.node.defn.title === false) {
            delete $scope.node.defn.title;
          } else {
            $scope.node.defn.title = false;
          }
        };

        $scope.toggleRequired = function() {
          getSet('required', !getSet('required'));
          return false;
        };

        $scope.toggleHelp = function(position) {
          getSet('help_' + position, $scope.propIsset('help_' + position) ? null : ($scope.getDefn()['help_' + position] || ts('Enter text')));
          return false;
        };

        // Getter/setter for definition props
        $scope.getSet = function(propName) {
          return _.wrap(propName, getSet);
        };

        // Getter/setter callback
        function getSet(propName, val) {
          if (arguments.length > 1) {
            var path = propName.split('.'),
              item = path.pop(),
              localDefn = drillDown($scope.node, ['defn'].concat(path)),
              fieldDefn = drillDown($scope.getDefn(), path);
            // Set the value if different than the field defn, otherwise unset it
            if (typeof val !== 'undefined' && (val !== fieldDefn[item] && !(!val && !fieldDefn[item]))) {
              localDefn[item] = val;
            } else {
              delete localDefn[item];
              clearOut($scope.node, ['defn'].concat(path));
            }
            return val;
          }
          return $scope.getProp(propName);
        }
        this.getSet = getSet;

        this.setEditingOptions = function(val) {
          $scope.editingOptions = val;
        };

        // Returns a reference to a path n-levels deep within an object
        function drillDown(parent, path) {
          var container = parent;
          _.each(path, function(level) {
            container[level] = container[level] || {};
            container = container[level];
          });
          return container;
        }

        // Recursively clears out empty arrays and objects
        function clearOut(parent, path) {
          var item;
          while (path.length && _.every(drillDown(parent, path), _.isEmpty)) {
            item = path.pop();
            delete drillDown(parent, path)[item];
          }
        }
      }
    };
  });

  angular.module('afGuiEditor').directive('afGuiEditOptions', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/editOptions.html',
      scope: true,
      require: '^^afGuiField',
      link: function ($scope, element, attrs, afGuiField) {
        $scope.field = afGuiField;
        $scope.options = JSON.parse(angular.toJson(afGuiField.getOptions()));
        var optionKeys = _.map($scope.options, 'key');
        $scope.deletedOptions = _.filter(JSON.parse(angular.toJson(afGuiField.getDefn().options || [])), function(item) {
          return !_.contains(optionKeys, item.key);
        });
        $scope.originalLabels = _.transform(afGuiField.getDefn().options || [], function(originalLabels, item) {
          originalLabels[item.key] = item.label;
        }, {});
      },
      controller: function ($scope) {
        var ts = $scope.ts = CRM.ts();

        $scope.deleteOption = function(option, $index) {
          $scope.options.splice($index, 1);
          $scope.deletedOptions.push(option);
        };

        $scope.restoreOption = function(option, $index) {
          $scope.deletedOptions.splice($index, 1);
          $scope.options.push(option);
        };

        $scope.save = function() {
          $scope.field.getSet('options', JSON.parse(angular.toJson($scope.options)));
          $scope.close();
        };

        $scope.close = function() {
          $scope.field.setEditingOptions(false);
          $('#afGuiEditor').removeClass('af-gui-editing-content');
        };
      }
    };
  });

  angular.module('afGuiEditor').directive('afGuiText', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/text.html',
      scope: {
        node: '=afGuiText'
      },
      require: '^^afGuiContainer',
      link: function($scope, element, attrs, container) {
        $scope.container = container;
      },
      controller: function($scope) {
        var ts = $scope.ts = CRM.ts();

        $scope.tags = {
          p: ts('Normal Text'),
          legend: ts('Fieldset Legend'),
          h1: ts('Heading 1'),
          h2: ts('Heading 2'),
          h3: ts('Heading 3'),
          h4: ts('Heading 4'),
          h5: ts('Heading 5'),
          h6: ts('Heading 6')
        };

        $scope.alignments = {
          'text-left': ts('Align left'),
          'text-center': ts('Align center'),
          'text-right': ts('Align right'),
          'text-justify': ts('Justify')
        };

        $scope.getAlign = function() {
          return _.intersection(splitClass($scope.node['class']), _.keys($scope.alignments))[0] || 'text-left';
        };

        $scope.setAlign = function(val) {
          modifyClasses($scope.node, _.keys($scope.alignments), val === 'text-left' ? null : val);
        };

        $scope.styles = _.transform(CRM.afformAdminData.styles, function(styles, val, key) {
          styles['text-' + key] = val;
        });

        // Getter/setter for ng-model
        $scope.getSetStyle = function(val) {
          if (arguments.length) {
            return modifyClasses($scope.node, _.keys($scope.styles), val === 'text-default' ? null : val);
          }
          return _.intersection(splitClass($scope.node['class']), _.keys($scope.styles))[0] || 'text-default';
        };

      }
    };
  });

  var richtextId = 0;
  angular.module('afGuiEditor').directive('afGuiMarkup', function($sce, $timeout) {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/markup.html',
      scope: {
        node: '=afGuiMarkup'
      },
      require: '^^afGuiContainer',
      link: function($scope, element, attrs, container) {
        $scope.container = container;
        // CRM.wysiwyg doesn't work without a dom id
        $scope.id = 'af-markup-editor-' + richtextId++;

        // When creating a new markup container, go straight to edit mode
        $timeout(function() {
          if ($scope.node['#markup'] === false) {
            $scope.edit();
          }
        });
      },
      controller: function($scope) {
        var ts = $scope.ts = CRM.ts();

        $scope.getMarkup = function() {
          return $sce.trustAsHtml($scope.node['#markup'] || '');
        };

        $scope.edit = function() {
          $('#afGuiEditor').addClass('af-gui-editing-content');
          $scope.editingMarkup = true;
          CRM.wysiwyg.create('#' + $scope.id);
          CRM.wysiwyg.setVal('#' + $scope.id, $scope.node['#markup'] || '<p></p>');
        };

        $scope.save = function() {
          $scope.node['#markup'] = CRM.wysiwyg.getVal('#' + $scope.id);
          $scope.close();
        };

        $scope.close = function() {
          CRM.wysiwyg.destroy('#' + $scope.id);
          $('#afGuiEditor').removeClass('af-gui-editing-content');
          // If a newly-added wysiwyg was canceled, just remove it
          if ($scope.node['#markup'] === false) {
            $scope.container.removeElement($scope.node);
          } else {
            $scope.editingMarkup = false;
          }
        };
      }
    };
  });


  angular.module('afGuiEditor').directive('afGuiButton', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/button.html',
      scope: {
        node: '=afGuiButton'
      },
      require: '^^afGuiContainer',
      link: function($scope, element, attrs, container) {
        $scope.container = container;
      },
      controller: function($scope) {
        var ts = $scope.ts = CRM.ts();

        // TODO: Add action selector to UI
        // $scope.actions = {
        //   "afform.submit()": ts('Submit Form')
        // };

        $scope.styles = _.transform(CRM.afformAdminData.styles, function(styles, val, key) {
          styles['btn-' + key] = val;
        });

        // Getter/setter for ng-model
        $scope.getSetStyle = function(val) {
          if (arguments.length) {
            return modifyClasses($scope.node, _.keys($scope.styles), ['btn', val]);
          }
          return _.intersection(splitClass($scope.node['class']), _.keys($scope.styles))[0] || '';
        };

        $scope.pickIcon = function() {
          openIconPicker($scope.node, 'crm-icon');
        };

      }
    };
  });

  // Connect bootstrap dropdown.js with angular
  // Allows menu content to be conditionally rendered only if open
  // This gives a large performance boost for a page with lots of menus
  angular.module('afGuiEditor').directive('afGuiMenu', function() {
    return {
      restrict: 'A',
      link: function($scope, element, attrs) {
        $scope.menu = {};
        element
          .on('show.bs.dropdown', function() {
            $scope.$apply(function() {
              $scope.menu.open = true;
            });
          })
          .on('hidden.bs.dropdown', function() {
            $scope.$apply(function() {
              $scope.menu.open = false;
            });
          });
      }
    };
  });

  // Menu item to control the border property of a node
  angular.module('afGuiEditor').directive('afGuiMenuItemBorder', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/menu-item-border.html',
      scope: {
        node: '=afGuiMenuItemBorder'
      },
      link: function($scope, element, attrs) {
        var ts = $scope.ts = CRM.ts();

        $scope.getSetBorderWidth = function(width) {
          return getSetBorderProp($scope.node, 0, arguments.length ? width : null);
        };

        $scope.getSetBorderStyle = function(style) {
          return getSetBorderProp($scope.node, 1, arguments.length ? style : null);
        };

        $scope.getSetBorderColor = function(color) {
          return getSetBorderProp($scope.node, 2, arguments.length ? color : null);
        };

        function getSetBorderProp(node, idx, val) {
          var border = getBorder(node) || ['1px', '', '#000000'];
          if (val === null) {
            return border[idx];
          }
          border[idx] = val;
          setStyle(node, 'border', val ? border.join(' ') : null);
        }

        function getBorder(node) {
          var border = _.map((getStyles(node).border || '').split(' '), _.trim);
          return border.length > 2 ? border : null;
        }
      }
    };
  });

  // Menu item to control the background property of a node
  angular.module('afGuiEditor').directive('afGuiMenuItemBackground', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/menu-item-background.html',
      scope: {
        node: '=afGuiMenuItemBackground'
      },
      link: function($scope, element, attrs) {
        var ts = $scope.ts = CRM.ts();

        $scope.getSetBackgroundColor = function(color) {
          if (!arguments.length) {
            return getStyles($scope.node)['background-color'] || '#ffffff';
          }
          setStyle($scope.node, 'background-color', color);
        };
      }
    };
  });

  // Editable titles using ngModel & html5 contenteditable
  // Cribbed from ContactLayoutEditor
  angular.module('afGuiEditor').directive("afGuiEditable", function() {
    return {
      restrict: "A",
      require: "ngModel",
      scope: {
        defaultValue: '='
      },
      link: function(scope, element, attrs, ngModel) {
        var ts = CRM.ts();

        function read() {
          var htmlVal = element.html();
          if (!htmlVal) {
            htmlVal = scope.defaultValue;
            element.text(htmlVal);
          }
          ngModel.$setViewValue(htmlVal);
        }

        ngModel.$render = function() {
          element.text(ngModel.$viewValue || scope.defaultValue);
        };

        // Special handling for enter and escape keys
        element.on('keydown', function(e) {
          // Enter: prevent line break and save
          if (e.which === 13) {
            e.preventDefault();
            element.blur();
          }
          // Escape: undo
          if (e.which === 27) {
            element.html(ngModel.$viewValue || scope.defaultValue);
            element.blur();
          }
        });

        element.on("blur change", function() {
          scope.$apply(read);
        });

        element.attr('contenteditable', 'true').addClass('crm-editable-enabled');
      }
    };
  });

  // Cribbed from the Api4 Explorer
  angular.module('afGuiEditor').directive('afGuiFieldValue', function() {
    return {
      scope: {
        field: '=afGuiFieldValue'
      },
      require: 'ngModel',
      link: function (scope, element, attrs, ctrl) {
        var ts = scope.ts = CRM.ts(),
          multi;

        function destroyWidget() {
          var $el = $(element);
          if ($el.is('.crm-form-date-wrapper .crm-hidden-date')) {
            $el.crmDatepicker('destroy');
          }
          if ($el.is('.select2-container + input')) {
            $el.crmEntityRef('destroy');
          }
          $(element).removeData().removeAttr('type').removeAttr('placeholder').show();
        }

        function makeWidget(field) {
          var $el = $(element),
            inputType = field.input_type,
            dataType = field.data_type;
          multi = field.serialize || dataType === 'Array';
          if (inputType === 'Date') {
            $el.crmDatepicker({time: (field.input_attrs && field.input_attrs.time) || false});
          }
          else if (field.fk_entity || field.options || dataType === 'Boolean') {
            if (field.fk_entity) {
              $el.crmEntityRef({entity: field.fk_entity, select:{multiple: multi}});
            } else if (field.options) {
              var options = _.transform(field.options, function(options, val) {
                options.push({id: val.key, text: val.label});
              }, []);
              $el.select2({data: options, multiple: multi});
            } else if (dataType === 'Boolean') {
              $el.attr('placeholder', ts('- select -')).crmSelect2({allowClear: false, multiple: multi, placeholder: ts('- select -'), data: [
                {id: '1', text: ts('Yes')},
                {id: '0', text: ts('No')}
              ]});
            }
          } else if (dataType === 'Integer' && !multi) {
            $el.attr('type', 'number');
          }
        }

        // Copied from ng-list but applied conditionally if field is multi-valued
        var parseList = function(viewValue) {
          // If the viewValue is invalid (say required but empty) it will be `undefined`
          if (_.isUndefined(viewValue)) return;

          if (!multi) {
            return viewValue;
          }

          var list = [];

          if (viewValue) {
            _.each(viewValue.split(','), function(value) {
              if (value) list.push(_.trim(value));
            });
          }

          return list;
        };

        // Copied from ng-list
        ctrl.$parsers.push(parseList);
        ctrl.$formatters.push(function(value) {
          return _.isArray(value) ? value.join(', ') : value;
        });

        // Copied from ng-list
        ctrl.$isEmpty = function(value) {
          return !value || !value.length;
        };

        scope.$watchCollection('field', function(field) {
          destroyWidget();
          if (field) {
            makeWidget(field);
          }
        });
      }
    };
  });

  function getStyles(node) {
    return !node || !node.style ? {} : _.transform(node.style.split(';'), function(styles, style) {
      var keyVal = _.map(style.split(':'), _.trim);
      if (keyVal.length > 1 && keyVal[1].length) {
        styles[keyVal[0]] = keyVal[1];
      }
    }, {});
  }

  function setStyle(node, name, val) {
    var styles = getStyles(node);
    styles[name] = val;
    if (!val) {
      delete styles[name];
    }
    if (_.isEmpty(styles)) {
      delete node.style;
    } else {
      node.style = _.transform(styles, function(combined, val, name) {
        combined.push(name + ': ' + val);
      }, []).join('; ');
    }
  }

  function modifyClasses(node, toRemove, toAdd) {
    var classes = splitClass(node['class']);
    if (toRemove) {
      classes = _.difference(classes, splitClass(toRemove));
    }
    if (toAdd) {
      classes = _.unique(classes.concat(splitClass(toAdd)));
    }
    node['class'] = classes.join(' ');
  }

  var editingIcon, editingIconProp;
  function openIconPicker(node, propName) {
    editingIcon = node;
    editingIconProp = propName;
    $('#af-gui-icon-picker ~ .crm-icon-picker-button').click();
  }

})(angular, CRM.$, CRM._);
