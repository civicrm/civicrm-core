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
