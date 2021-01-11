(function(angular, $, _) {
  "use strict";
  angular.module('afGuiEditor', CRM.angRequires('afGuiEditor'))

    .service('afGui', function(crmApi4, $parse, $q) {

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

})(angular, CRM.$, CRM._);
