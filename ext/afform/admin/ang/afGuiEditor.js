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
                if (looksLikeJs(prop)) {
                  try {
                    item[key] = $parse(prop)({ts: CRM.ts('afform')});
                  } catch (e) {
                  }
                }
              }
            });
          }
        });
      }

      function looksLikeJs(str) {
        str = _.trim(str);
        let firstChar = str.charAt(0);
        let lastChar = str.slice(-1);
        return (firstChar === '{' && lastChar === '}') ||
          (firstChar === '[' && lastChar === ']') ||
          str.slice(0, 3) === 'ts(';
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

      // Check if a node has class(es)
      function hasClass(node, className) {
        if (!node['class']) {
          return false;
        }
        var classes = splitClass(node['class']),
          classNames = className.split(' ');
        return _.intersection(classes, classNames).length === classNames.length;
      }

      function modifyClasses(node, toRemove, toAdd) {
        var classes = splitClass(node['class']);
        if (toRemove) {
          classes = _.difference(classes, splitClass(toRemove));
        }
        if (toAdd) {
          classes = _.unique(classes.concat(splitClass(toAdd)));
        }
        if (classes.length) {
          node['class'] = classes.join(' ');
        } else if ('class' in node) {
          delete node['class'];
        }
      }

      // Convert value to javascript notation
      function encode(value) {
        const encoded = JSON.stringify(value);
        const split = encoded.split('"');
        // Convert double-quotes to single-quotes if possible
        if (split.length === 3 && split[0] === '' && split[2] === '' && encoded.indexOf("'") < 0) {
          return "'" + split[1] + "'";
        }
        return encoded;
      }

      // Convert javascript notation to value
      function decode(encoded) {
        // Single-quoted string
        if (encoded.startsWith("'") && encoded.charAt(encoded.length - 1) === "'") {
          return encoded.substring(1, encoded.length - 1);
        }
        // Anything else
        return JSON.parse(encoded);
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
          // Add behavior data
          CRM.afGuiEditor.behaviors = CRM.afGuiEditor.behaviors || {};
          _.extend(CRM.afGuiEditor.behaviors, data.behaviors);
          // Add entities
          _.each(data.entities, function(entity, entityName) {
            if (!CRM.afGuiEditor.entities[entityName]) {
              CRM.afGuiEditor.entities[entityName] = entity;
            }
          });
          // Combine entities with fields
          _.each(data.fields, function(fields, entityName) {
            if (CRM.afGuiEditor.entities[entityName]) {
              CRM.afGuiEditor.entities[entityName].fields = fields;
            }
          });
          _.each(data.search_displays, function(display) {
            CRM.afGuiEditor.searchDisplays[display['saved_search_id.name'] + (display.name ? '.' + display.name : '')] = display;
          });
        },

        meta: _.extend(CRM.afGuiEditor, CRM.afAdmin),

        getEntity: function(entityName) {
          return CRM.afGuiEditor.entities[entityName];
        },

        getField: function(entityName, fieldName) {
          var fields = CRM.afGuiEditor.entities[entityName].fields;
          return fields[fieldName] || fields[fieldName.substr(fieldName.indexOf('.') + 1)];
        },

        getSearchDisplay: function(searchName, displayName) {
          return CRM.afGuiEditor.searchDisplays[searchName + (displayName ? '.' + displayName : '')];
        },

        getAllSearchDisplays: function() {
          var links = [],
            searchNames = [],
            deferred = $q.defer();
          // Non-aggregated query will return the same search multiple times - once per display
          crmApi4('SavedSearch', 'get', {
            select: ['name', 'label', 'display.name', 'display.label', 'display.type:name', 'display.type:icon'],
            where: [['api_entity', 'IS NOT NULL'], ['api_params', 'IS NOT NULL'], ['is_template', '=', false]],
            join: [['SearchDisplay AS display', 'LEFT', ['id', '=', 'display.saved_search_id']]],
            orderBy: {'label':'ASC'}
          }).then(function(searches) {
            _.each(searches, function(search) {
              // Add default display for each search (track searchNames in a var to just add once per search)
              if (!_.includes(searchNames, search.name)) {
                searchNames.push(search.name);
                links.push({
                  key: search.name,
                  url: '#create/search/' + search.name,
                  label: search.label + ': ' + ts('Search results table'),
                  tag: 'crm-search-display-table',
                  icon: 'fa-table'
                });
              }
              // If the search has no displays (other than the default) this will be empty
              if (search['display.name']) {
                links.push({
                  key: search.name + '.' + search['display.name'],
                  url: '#create/search/' + search.name + '.' + search['display.name'],
                  label: search.label + ': ' + search['display.label'],
                  tag: search['display.type:name'],
                  icon: search['display.type:icon']
                });
              }
            });
            deferred.resolve(links);
          });
          return deferred.promise;
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

        // Recursively searches part of a form and returns all elements matching predicate
        // Will recurse into block elements
        // Will stop recursing when it encounters an element matching 'exclude'
        getFormElements: function getFormElements(collection, predicate, exclude) {
          var childMatches = [],
            items = _.filter(collection, predicate),
            isExcluded = exclude ? (_.isFunction(exclude) ? exclude : _.matches(exclude)) : _.constant(false);
          function isIncluded(item) {
            return !isExcluded(item);
          }
          _.each(_.filter(collection, isIncluded), function(item) {
            if (_.isPlainObject(item) && item['#children']) {
              childMatches = getFormElements(item['#children'], predicate, exclude);
            } else if (item['#tag'] && item['#tag'] in CRM.afGuiEditor.blocks) {
              childMatches = getFormElements(CRM.afGuiEditor.blocks[item['#tag']].layout, predicate, exclude);
            }
            if (childMatches.length) {
              Array.prototype.push.apply(items, childMatches);
            }
          });
          return items;
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
        hasClass: hasClass,
        modifyClasses: modifyClasses,
        getStyles: getStyles,
        setStyle: setStyle,

        // Convert search display filters to js notation
        stringifyDisplayFilters: function(filters) {
          if (!filters || !filters.length) {
            return null;
          }
          const output = filters.map((filter) => {
            const keyVal = [
              // Enclose the key in quotes unless it is purely alphanumeric
              filter.name.match(/\W/) ? encode(filter.name) : filter.name,
            ];
            // Object dot notation
            if (filter.mode !== 'val' && !filter.value.match(/\W/)) {
              keyVal.push(filter.mode + '.' + filter.value);
            }
            // Object bracket notation
            else if (filter.mode !== 'val') {
              keyVal.push(filter.mode + '[' + encode(filter.value) + ']');
            }
            // Literal value
            else {
              keyVal.push(encode(filter.value));
            }
            return keyVal.join(': ');
          });
          return '{' + output.join(', ') + '}';
        },

        // Convert search display filter string to array
        parseDisplayFilters: function(filterString) {
          if (!filterString || filterString === '{}') {
            return [];
          }
          // Split contents by commas, ignoring commas inside quotes
          const rawValues = _.trim(filterString, '{}').split(/,(?=(?:(?:[^']*'){2})*[^']*$)/);
          return rawValues.map((raw) => {
            raw = _.trim(raw);
            let split;
            if (raw.charAt(0) === '"') {
              split = raw.slice(1).split(/"[ ]*:/);
            } else if (raw.charAt(0) === "'") {
              split = raw.slice(1).split(/'[ ]*:/);
            } else {
              split = raw.split(':');
            }
            const key = _.trim(split[0]);
            const value = _.trim(split[1]);
            let mode = 'val';
            if (value.startsWith('routeParams')) {
              mode = 'routeParams';
            } else if (value.startsWith('options')) {
              mode = 'options';
            }
            let info = {
              name: key,
              mode: mode
            };
            // Object dot notation
            if (mode !== 'val' && value.startsWith(mode + '.')) {
              info.value = value.replace(mode + '.', '');
            }
            // Object bracket notation
            else if (mode !== 'val') {
              info.value = decode(value.substring(value.indexOf('[') + 1, value.lastIndexOf(']')));
            }
            // Literal value
            else {
              info.value = decode(value);
            }
            return info;
          }, []);
        },

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
    CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmIconPicker.js').then(function() {
      $('#af-gui-icon-picker').crmIconPicker();
    });
    // Add css classes while dragging
    $(document)
      // When dragging an item over a container, add a class to highlight the target
      .on('sortover', function(e) {
        $('.af-gui-container').removeClass('af-gui-dragtarget');
        $(e.target).closest('.af-gui-container').addClass('af-gui-dragtarget');
      })
      // Un-highlight when dragging out of a container
      .on('sortout', '.af-gui-container', function() {
        $(this).removeClass('af-gui-dragtarget');
      })
      // Add body class which puts the entire UI into a "dragging" state
      .on('sortstart', '#afGuiEditor', function() {
        $('body').addClass('af-gui-dragging');
      })
      // Ensure dragging classes are removed when not sorting
      // Listening to multiple event types because sort* events are not 100% reliable
      .on('sortbeforestop mouseenter', function() {
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
              element.closest('#afGuiEditor-canvas').addClass('af-gui-menu-open');
            });
          })
          .on('hidden.bs.dropdown', function() {
            $scope.$apply(function() {
              $scope.menu.open = false;
              element.closest('#afGuiEditor-canvas').removeClass('af-gui-menu-open');
            });
          });
      }
    };
  });

})(angular, CRM.$, CRM._);
