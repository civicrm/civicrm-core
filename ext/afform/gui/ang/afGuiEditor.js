(function(angular, $, _) {
  angular.module('afGuiEditor', CRM.angRequires('afGuiEditor'));

  var editingIcon;

  angular.module('afGuiEditor').directive('afGuiEditor', function(crmApi4, $parse, $timeout) {
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
                editingIcon['crm-icon'] = $('#af-gui-icon-picker').val();
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
        $scope.selectedEntity = null;
        $scope.meta = this.meta = CRM.afformAdminData;
        this.scope = $scope;
        var editor = $scope.editor = this;
        var newForm = {
          title: ts('Untitled Form'),
          layout: [{
            '#tag': 'af-form',
            ctrl: 'modelListCtrl',
            '#children': []
          }]
        };
        if ($scope.afGuiEditor.name && $scope.afGuiEditor.name != '0') {
          // Todo - show error msg if form is not found
          crmApi4('Afform', 'get', {where: [['name', '=', $scope.afGuiEditor.name]], layoutFormat: 'shallow'}, 0)
            .then(initialize);
        }
        else {
          $timeout(function() {
            initialize(_.cloneDeep(newForm));
            editor.addEntity('Contact');
            $scope.layout['#children'].push({
              "#tag": "button",
              "class": 'af-button btn btn-primary',
              "crm-icon": 'fa-check',
              "ng-click": "modelListCtrl.submit()",
              "#children": [
                {
                  "#text": "Submit"
                }
              ]
            });
          });
        }

        function initialize(afform) {
          $scope.afform = afform;
          $scope.changesSaved = 1;
          // Remove empty text nodes, they just create clutter
          removeRecursive($scope.afform.layout, function(item) {
            return ('#text' in item) && _.trim(item['#text']).length === 0;
          });
          $scope.layout = getTags($scope.afform.layout, 'af-form')[0];
          evaluate($scope.layout['#children']);
          $scope.entities = getTags($scope.layout['#children'], 'af-entity', 'name');

          // Set changesSaved to true on initial load, false thereafter whenever changes are made to the model
          $scope.$watch('afform', function () {
            $scope.changesSaved = $scope.changesSaved === 1;
          }, true);
        }

        this.addEntity = function(entityType) {
          var existingEntitiesofThisType = _.map(_.filter($scope.entities, {type: entityType}), 'name'),
            num = existingEntitiesofThisType.length + 1;
          // Give this new entity a unique name
          while (_.contains(existingEntitiesofThisType, entityType + num)) {
            num++;
          }
          $scope.entities[entityType + num] = _.assign($parse(this.meta.defaults[entityType])($scope), {
            '#tag': 'af-entity',
            type: entityType,
            name: entityType + num,
            label: entityType + ' ' + num
          });
          $scope.layout['#children'].unshift($scope.entities[entityType + num]);
          $scope.layout['#children'].push({
            '#tag': 'fieldset',
            'af-fieldset': entityType + num,
            '#children': [
              {
                '#tag': 'legend',
                'class': 'af-text',
                '#children': [
                  {
                    '#text': entityType + ' ' + num
                  }
                ]
              }
            ]
          });
          return entityType + num;
        };

        this.removeEntity = function(entityName) {
          delete $scope.entities[entityName];
          _.remove($scope.layout['#children'], {'#tag': 'af-entity', name: entityName});
          removeRecursive($scope.layout['#children'], {'af-fieldset': entityName});
          this.selectEntity(null);
        };

        this.selectEntity = function(entityName) {
          $scope.selectedEntity = entityName;
        };

        this.getField = function(entityType, fieldName) {
          return $scope.meta.fields[entityType][fieldName];
        };

        this.getEntity = function(entityName) {
          return $scope.entities[entityName];
        };

        this.getSelectedEntity = function() {
          return $scope.selectedEntity;
        };

        $scope.addEntity = function(entityType) {
          var entityName = editor.addEntity(entityType);
          editor.selectEntity(entityName);
        };

        $scope.save = function() {
          $scope.saving = true;
          CRM.api4('Afform', 'save', {records: [JSON.parse(angular.toJson($scope.afform))]})
            .then(function () {
              $scope.$apply(function () {
                $scope.saving = false;
                $scope.changesSaved = true;
              });
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

  function getTags(collection, tagName, indexBy) {
    var items = [];
    _.each(collection, function(item) {
      if (item && typeof item === 'object') {
        if (item['#tag'] === tagName) {
          items.push(item);
        }
        var childTags = item['#children'] ? getTags(item['#children'], tagName) : [];
        if (childTags.length) {
          Array.prototype.push.apply(items, childTags);
        }
      }
    });
    return indexBy ? _.indexBy(items, indexBy) : items;
  }

  // Turns a space-separated list (e.g. css classes) into an array
  function splitClass(str) {
    if (_.isArray(str)) {
      return str;
    }
    return str ? _.unique(_.trim(str).split(/\s+/g)) : [];
  }

  function removeRecursive(collection, removeParams) {
    _.remove(collection, removeParams);
    _.each(collection, function(item) {
      if (_.isPlainObject(item) && item['#children']) {
        removeRecursive(item['#children'], removeParams);
      }
    });
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

        $scope.valuesFields = function() {
          var fields = $scope.editor ? _.transform($scope.editor.meta.fields[$scope.entity.type], function(fields, field) {
            fields.push({id: field.name, text: field.title, disabled: $scope.fieldInUse(field.name)});
          }, []) : [];
          return {results: fields};
        };

        $scope.removeValue = function(entity, fieldName) {
          delete entity.data[fieldName];
        };

        $scope.buildFieldList = function() {
          $scope.fieldList.length = 0;
          var search = $scope.controls.fieldSearch ? $scope.controls.fieldSearch.toLowerCase() : null;
          _.each($scope.editor.meta.fields[$scope.entity.type], function(field) {
            if (!search || _.contains(field.name, search) || _.contains(field.title.toLowerCase(), search)) {
              $scope.fieldList.push({
                "#tag": "af-field",
                name: field.name,
                defn: {}
              });
            }
          });
        };

        $scope.clearSearch = function() {
          $scope.controls.fieldSearch = '';
        };

        $scope.rebuildFieldList = function() {
          $timeout(function() {
            $scope.$apply(function() {
              $scope.buildFieldList();
            });
          });
        };

        // Checks if a field is on the form or set as a value
        $scope.fieldInUse = function(fieldName) {
          var data = $scope.entity.data || {},
            found = false;
          if (fieldName in data) {
            return true;
          }
          return check($scope.editor.scope.layout['#children']);
          function check(group) {
            _.each(group, function(item) {
              if (found) {
                return false;
              }
              if (_.isPlainObject(item)) {
                if ((!item['af-fieldset'] || (item['af-fieldset'] === $scope.entity.name)) && item['#children']) {
                  check(item['#children']);
                }
                if (item['#tag'] === 'af-field' && item.name === fieldName) {
                  found = true;
                }
              }
            });
            return found;
          }
        };

        $scope.$watch('controls.addValue', function(fieldName) {
          if (fieldName) {
            if (!$scope.entity.data) {
              $scope.entity.data = {};
            }
            $scope.entity.data[fieldName] = '';
            $scope.controls.addValue = '';
          }
        });

        $scope.$watch('controls.fieldSearch', $scope.buildFieldList);
      }
    };
  });

  angular.module('afGuiEditor').directive('afGuiBlock', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/block.html',
      scope: {
        node: '=afGuiBlock',
        entityName: '='
      },
      require: '^^afGuiEditor',
      link: function($scope, element, attrs, editor) {
        $scope.editor = editor;
      },
      controller: function($scope) {
        var block = $scope.block = this;
        var ts = $scope.ts = CRM.ts();
        this.node = $scope.node;

        this.modifyClasses = function(item, toRemove, toAdd) {
          var classes = splitClass(item['class']);
          if (toRemove) {
            classes = _.difference(classes, splitClass(toRemove));
          }
          if (toAdd) {
            classes = _.unique(classes.concat(splitClass(toAdd)));
          }
          item['class'] = classes.join(' ');
        };

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
          var classes = splitClass(node['class']);
          if (_.contains(classes, 'af-block')) {
            return 'block';
          }
          if (_.contains(classes, 'af-text')) {
            return 'text';
          }
          if (_.contains(classes, 'af-button')) {
            return 'button';
          }
          return null;
        };

        $scope.addBlock = function(type, props) {
          var classes = type.split('.');
          var newBlock = _.defaults({
            '#tag': classes.shift(),
            'class': classes.join(' '),
            '#children': classes[0] === 'af-block' ? [] : [{'#text': ts('Enter text')}]
          }, props);
          $scope.node['#children'].push(newBlock);
        };

        this.removeBlock = function(node) {
          removeRecursive($scope.editor.scope.layout['#children'], {$$hashKey: node.$$hashKey});
        };

        $scope.isSelectedFieldset = function(entityName) {
          return entityName === $scope.editor.getSelectedEntity();
        };

        $scope.selectEntity = function() {
          if ($scope.node['af-fieldset']) {
            $scope.editor.selectEntity($scope.node['af-fieldset']);
          }
        };

        // Validates that a drag-n-drop action is allowed
        $scope.onDrop = function(event, ui) {
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
            if (($item.is('[data-entity]') || $item.has('[data-entity]').length) && $target.closest('[data-entity]').length) {
              return sort.cancel();
            }
          }
        };

        $scope.tags = {
          div: ts('Block'),
          fieldset: ts('Fieldset')
        };

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
          var classes = ['af-block'];
          if (val !== 'af-layout-rows') {
            classes.push(val);
          }
          block.modifyClasses($scope.node, _.keys($scope.layouts), classes);
        };

        $scope.getSetBorderWidth = function(width) {
          return getSetBorderProp(0, arguments.length ? width : null);
        };

        $scope.getSetBorderStyle = function(style) {
          return getSetBorderProp(1, arguments.length ? style : null);
        };

        $scope.getSetBorderColor = function(color) {
          return getSetBorderProp(2, arguments.length ? color : null);
        };

        $scope.getSetBackgroundColor = function(color) {
          if (!arguments.length) {
            return block.getStyles($scope.node)['background-color'] || '#ffffff';
          }
          block.setStyle($scope.node, 'background-color', color);
        };
        
        function getSetBorderProp(idx, val) {
          var border = getBorder() || ['1px', '', '#000000'];
          if (val === null) {
            return border[idx];
          }
          border[idx] = val;
          block.setStyle($scope.node, 'border', val ? border.join(' ') : null);
        }

        this.getStyles = function(node) {
          return !node || !node.style ? {} : _.transform(node.style.split(';'), function(styles, style) {
            var keyVal = _.map(style.split(':'), _.trim);
            if (keyVal.length > 1 && keyVal[1].length) {
              styles[keyVal[0]] = keyVal[1];
            }
          }, {});
        };

        this.setStyle = function(node, name, val) {
          var styles = block.getStyles(node);
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
        };

        function getBorder() {
          var border = _.map((block.getStyles($scope.node).border || '').split(' '), _.trim);
          return border.length > 2 ? border : null;
        }

      }
    };
  });

  angular.module('afGuiEditor').directive('afGuiField', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/field.html',
      scope: {
        node: '=afGuiField',
        entityName: '='
      },
      require: ['^^afGuiEditor', '^^afGuiBlock'],
      link: function($scope, element, attrs, ctrls) {
        $scope.editor = ctrls[0];
        $scope.block = ctrls[1];
      },
      controller: function($scope) {
        var ts = $scope.ts = CRM.ts();
        $scope.editingOptions = false;
        var yesNo = [
          {key: '1', label: ts('Yes')},
          {key: '0', label: ts('No')}
        ];

        $scope.getEntity = function() {
          return $scope.editor ? $scope.editor.getEntity($scope.entityName) : {};
        };

        $scope.getDefn = this.getDefn = function() {
          return $scope.editor ? $scope.editor.getField($scope.getEntity().type, $scope.node.name) : {};
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
          $('#afGuiEditor').addClass('af-gui-editing-options');
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
            if (typeof val !== 'undefined' && val !== fieldDefn[item]) {
              localDefn[item] = val;
            } else {
              delete localDefn[item];
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
          $('#afGuiEditor').removeClass('af-gui-editing-options');
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
      require: '^^afGuiBlock',
      link: function($scope, element, attrs, block) {
        $scope.block = block;
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
          $scope.block.modifyClasses($scope.node, _.keys($scope.alignments), val === 'text-left' ? null : val);
        };

        $scope.styles = _.transform(CRM.afformAdminData.styles, function(styles, val, key) {
          styles['text-' + key] = val;
        });

        // Getter/setter for ng-model
        $scope.getSetStyle = function(val) {
          if (arguments.length) {
            return $scope.block.modifyClasses($scope.node, _.keys($scope.styles), val === 'text-default' ? null : val);
          }
          return _.intersection(splitClass($scope.node['class']), _.keys($scope.styles))[0] || 'text-default';
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
      require: '^^afGuiBlock',
      link: function($scope, element, attrs, block) {
        $scope.block = block;
      },
      controller: function($scope) {
        var ts = $scope.ts = CRM.ts();

        // TODO: Add action selector to UI
        // $scope.actions = {
        //   "modelListCtrl.submit()": ts('Submit Form')
        // };

        $scope.styles = _.transform(CRM.afformAdminData.styles, function(styles, val, key) {
          styles['btn-' + key] = val;
        });

        // Getter/setter for ng-model
        $scope.getSetStyle = function(val) {
          if (arguments.length) {
            return $scope.block.modifyClasses($scope.node, _.keys($scope.styles), ['btn', val]);
          }
          return _.intersection(splitClass($scope.node['class']), _.keys($scope.styles))[0] || '';
        };

        $scope.pickIcon = function() {
          editingIcon = $scope.node;
          $('#af-gui-icon-picker ~ .crm-icon-picker-button').click();
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
            element.html(htmlVal);
          }
          ngModel.$setViewValue(htmlVal);
        }

        ngModel.$render = function() {
          element.html(ngModel.$viewValue || scope.defaultValue);
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

})(angular, CRM.$, CRM._);
