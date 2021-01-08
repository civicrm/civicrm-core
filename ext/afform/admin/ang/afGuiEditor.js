(function(angular, $, _) {
  "use strict";
  angular.module('afGuiEditor', CRM.angRequires('afGuiEditor'))

    .service('afAdmin', function(crmApi4, $parse, $q) {

      // Parse strings of javascript that php couldn't interpret
      function evaluate(collection) {
        _.each(collection, function(item) {
          if (_.isPlainObject(item)) {
            evaluate(item['#children']);
            _.each(item, function(node, idx) {
              if (_.isString(node)) {
                var str = _.trim(node);
                if (str[0] === '{' || str[0] === '[' || str.slice(0, 3) === 'ts(') {
                  item[idx] = $parse(str)({ts: CRM.ts('afform')});
                }
              }
            });
          }
        });
      }

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

      // Turns a space-separated list (e.g. css classes) into an array
      function splitClass(str) {
        if (_.isArray(str)) {
          return str;
        }
        return str ? _.unique(_.trim(str).split(/\s+/g)) : [];
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

      return {
        // Initialize/refresh data about the current afform + available blocks
        initialize: function(afName) {
          var promise = crmApi4('Afform', 'get', {
            layoutFormat: 'shallow',
            formatWhitespace: true,
            where: [afName ? ["OR", [["name", "=", afName], ["block", "IS NOT NULL"]]] : ["block", "IS NOT NULL"]]
          });
          promise.then(function(afforms) {
            CRM.afGuiEditor.blocks = {};
            _.each(afforms, function(form) {
              evaluate(form.layout);
              if (form.block) {
                CRM.afGuiEditor.blocks[form.directive_name] = form;
              }
            });
          });
          return promise;
        },

        meta: CRM.afGuiEditor,

        getField: function(entityType, fieldName) {
          return CRM.afGuiEditor.entities[entityType].fields[fieldName];
        },

        // Recursively searches a collection and its children using _.filter
        // Returns an array of all matches, or an object if the indexBy param is used
        findRecursive: function findRecursive(collection, predicate, indexBy) {
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
        },

        // Applies _.remove() to an item and its children
        removeRecursive: function removeRecursive(collection, removeParams) {
          _.remove(collection, removeParams);
          _.each(collection, function(item) {
            if (_.isPlainObject(item) && item['#children']) {
              removeRecursive(item['#children'], removeParams);
            }
          });
        },

        splitClass: splitClass,
        modifyClasses: modifyClasses,
        getStyles: getStyles,
        setStyle: setStyle,

        pickIcon: function() {
          var deferred = $q.defer();
          $('#af-gui-icon-picker').off('change').siblings('.crm-icon-picker-button').click();
          $('#af-gui-icon-picker').on('change', function() {
            deferred.resolve($(this).val());
          });
          return deferred.promise;
        }
      };
    });

  // Shoehorn in a non-angular widget for picking icons
  $(function() {
    $('#crm-container').append('<div style="display:none"><input id="af-gui-icon-picker"></div>');
    CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmIconPicker.js').done(function() {
      $('#af-gui-icon-picker').crmIconPicker();
    });
  });

  angular.module('afGuiEditor').directive('afGuiContainer', function(crmApi4, dialogService, afAdmin) {
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
          afAdmin.pickIcon().then(function(val) {
            $scope.node['add-icon'] = val;
          });
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
          return _.intersection(afAdmin.splitClass($scope.node['class']), _.keys($scope.layouts))[0] || 'af-layout-rows';
        };

        $scope.setLayout = function(val) {
          var classes = ['af-container'];
          if (val !== 'af-layout-rows') {
            classes.push(val);
          }
          afAdmin.modifyClasses($scope.node, _.keys($scope.layouts), classes);
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
      controller: function($scope, afAdmin) {
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
          var classes = afAdmin.splitClass(node['class']),
            types = ['af-container', 'af-text', 'af-button', 'af-markup'],
            type = _.intersection(types, classes);
          return type.length ? type[0].replace('af-', '') : null;
        };

        this.removeElement = function(element) {
          afAdmin.removeRecursive($scope.getSetChildren(), {$$hashKey: element.$$hashKey});
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
      controller: function($scope, afAdmin) {
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
          return $scope.editor ? afAdmin.getField($scope.container.getFieldEntityType(), $scope.node.name) : {};
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
          if ($scope.node.defn.label === false) {
            delete $scope.node.defn.label;
          } else {
            $scope.node.defn.label = false;
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
      controller: function($scope, afAdmin) {
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
          return _.intersection(afAdmin.splitClass($scope.node['class']), _.keys($scope.alignments))[0] || 'text-left';
        };

        $scope.setAlign = function(val) {
          afAdmin.modifyClasses($scope.node, _.keys($scope.alignments), val === 'text-left' ? null : val);
        };

        $scope.styles = _.transform(CRM.afGuiEditor.styles, function(styles, val, key) {
          styles['text-' + key] = val;
        });

        // Getter/setter for ng-model
        $scope.getSetStyle = function(val) {
          if (arguments.length) {
            return afAdmin.modifyClasses($scope.node, _.keys($scope.styles), val === 'text-default' ? null : val);
          }
          return _.intersection(afAdmin.splitClass($scope.node['class']), _.keys($scope.styles))[0] || 'text-default';
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
      controller: function($scope, afAdmin) {
        var ts = $scope.ts = CRM.ts();

        // TODO: Add action selector to UI
        // $scope.actions = {
        //   "afform.submit()": ts('Submit Form')
        // };

        $scope.styles = _.transform(CRM.afGuiEditor.styles, function(styles, val, key) {
          styles['btn-' + key] = val;
        });

        // Getter/setter for ng-model
        $scope.getSetStyle = function(val) {
          if (arguments.length) {
            return afAdmin.modifyClasses($scope.node, _.keys($scope.styles), ['btn', val]);
          }
          return _.intersection(afAdmin.splitClass($scope.node['class']), _.keys($scope.styles))[0] || '';
        };

        $scope.pickIcon = function() {
          afAdmin.pickIcon().then(function(val) {
            $scope.node['crm-icon'] = val;
          });
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
  angular.module('afGuiEditor').directive('afGuiMenuItemBorder', function(afAdmin) {
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
          afAdmin.setStyle(node, 'border', val ? border.join(' ') : null);
        }

        function getBorder(node) {
          var border = _.map((afAdmin.getStyles(node).border || '').split(' '), _.trim);
          return border.length > 2 ? border : null;
        }
      }
    };
  });

  // Menu item to control the background property of a node
  angular.module('afGuiEditor').directive('afGuiMenuItemBackground', function(afAdmin) {
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
            return afAdmin.getStyles($scope.node)['background-color'] || '#ffffff';
          }
          afAdmin.setStyle($scope.node, 'background-color', color);
        };
      }
    };
  });

})(angular, CRM.$, CRM._);
