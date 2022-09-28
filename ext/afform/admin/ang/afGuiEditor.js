(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor', CRM.angRequires('afGuiEditor'))

    .service('afGui', function(crmApi4, $parse, $q) {

      // Parse strings of javascript that php couldn't interpret
      // TODO: Figure out which attributes actually need to be evaluated, as a whitelist would be less error-prone than a blacklist
      var doNotEval = ['filters'];
      function evaluate(collection) {
        _.each(collection, function(item) {
          if (_.isPlainObject(item)) {
            evaluate(item['#children']);
            _.each(item, function(prop, key) {
              if (_.isString(prop) && !_.includes(doNotEval, key)) {
                var str = _.trim(prop);
                if (str[0] === '{' || str[0] === '[' || str.slice(0, 3) === 'ts(') {
                  item[key] = $parse(str)({ts: CRM.ts('afform')});
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
        // Called when loading a new afform for editing - clears out stale metadata
        resetMeta: function() {
          _.each(CRM.afGuiEditor.entities, function(entity, type) {
            // Skip the "*" pseudo-entity which should always have an empty list of fields
            if (entity.fields && type !== '*') {
              delete entity.fields;
            }
          });
          CRM.afGuiEditor.blocks = {};
          CRM.afGuiEditor.searchDisplays = {};
        },

        // Takes the results from api.Afform.loadAdminData and processes the metadata
        // Note this runs once when loading a new afform for editing (just after this.resetMeta is called)
        // and it also runs when adding new entities or blocks to the form.
        addMeta: function(data) {
          evaluate(data.definition.layout);
          if (data.definition.type === 'block' && data.definition.name) {
            CRM.afGuiEditor.blocks[data.definition.directive_name] = data.definition;
          }
          // Add new or updated blocks
          _.each(data.blocks, function(block) {
            // Avoid overwriting complete block record with an incomplete one
            if (!CRM.afGuiEditor.blocks[block.directive_name] || block.layout) {
              if (block.layout) {
                evaluate(block.layout);
              }
              CRM.afGuiEditor.blocks[block.directive_name] = block;
            }
          });
          _.each(data.entities, function(entity, entityName) {
            if (!CRM.afGuiEditor.entities[entityName]) {
              CRM.afGuiEditor.entities[entityName] = entity;
            }
          });
          _.each(data.fields, function(fields, entityName) {
            if (CRM.afGuiEditor.entities[entityName]) {
              CRM.afGuiEditor.entities[entityName].fields = fields;
            }
          });
          // Optimization - since contact fields are a combination of these three,
          // the server doesn't send contact fields if sending contact-type fields
          if ('Individual' in data.fields || 'Household' in data.fields || 'Organization' in data.fields) {
            CRM.afGuiEditor.entities.Contact.fields = _.assign({},
              (CRM.afGuiEditor.entities.Individual || {}).fields,
              (CRM.afGuiEditor.entities.Household || {}).fields,
              (CRM.afGuiEditor.entities.Organization || {}).fields
            );
          }
          _.each(data.search_displays, function(display) {
            CRM.afGuiEditor.searchDisplays[display['saved_search.name'] + '.' + display.name] = display;
          });
        },

        meta: CRM.afGuiEditor,

        getEntity: function(entityName) {
          return CRM.afGuiEditor.entities[entityName];
        },

        getField: function(entityName, fieldName) {
          var fields = CRM.afGuiEditor.entities[entityName].fields;
          return fields[fieldName] || fields[fieldName.substr(fieldName.indexOf('.') + 1)];
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

  $(function() {
    // Shoehorn in a non-angular widget for picking icons
    $('#crm-container').append('<div style="display:none"><input id="af-gui-icon-picker"></div>');
    CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmIconPicker.js').done(function() {
      $('#af-gui-icon-picker').crmIconPicker();
    });
    // Add css class while dragging
    $(document)
      .on('sortover', function(e) {
        $('.af-gui-container').removeClass('af-gui-dragtarget');
        $(e.target).closest('.af-gui-container').addClass('af-gui-dragtarget');
      })
      .on('sortout', '.af-gui-container', function() {
        $(this).removeClass('af-gui-dragtarget');
      })
      .on('sortstart', '#afGuiEditor', function() {
        $('body').addClass('af-gui-dragging');
      })
      .on('sortstop', function() {
        $('body').removeClass('af-gui-dragging');
        $('.af-gui-dragtarget').removeClass('af-gui-dragtarget');
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
