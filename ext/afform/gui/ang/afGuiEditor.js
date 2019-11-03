(function(angular, $, _) {
  angular.module('afGuiEditor', CRM.angRequires('afGuiEditor'));

  angular.module('afGuiEditor').directive('afGuiEditor', function(crmApi4, $parse, $timeout) {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/main.html',
      scope: {
        afGuiEditor: '='
      },
      controller: function($scope) {
        $scope.ts = CRM.ts();
        $scope.afform = null;
        $scope.selectedEntity = null;
        $scope.meta = CRM.afformAdminData;
        $scope.controls = {};
        $scope.fieldList = {};
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
          });
        }

        function initialize(afform) {
          $scope.afform = afform;
          $scope.layout = getTags($scope.afform.layout, 'af-form')[0];
          evaluate($scope.layout['#children']);
          $scope.entities = getTags($scope.layout['#children'], 'af-entity', 'name');
          expandFields($scope.layout['#children']);
          _.each(_.keys($scope.entities), buildFieldList);
        }

        this.addEntity = function(entityType) {
          var existingEntitiesofThisType = _.map(_.filter($scope.entities, {type: entityType}), 'name'),
            num = existingEntitiesofThisType.length + 1;
          // Give this new entity a unique name
          while (_.contains(existingEntitiesofThisType, entityType + num)) {
            num++;
          }
          $scope.entities[entityType + num] = {
            '#tag': 'af-entity',
            type: entityType,
            name: entityType + num,
            label: entityType + ' ' + num
          };
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
          buildFieldList(entityType + num);
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
          return _.filter($scope.meta.fields[entityType], {name: fieldName})[0];
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

        $scope.rebuildFieldList = function() {
          $timeout(function() {
            $scope.$apply(function() {
              buildFieldList($scope.selectedEntity);
            });
          });
        };

        function buildFieldList(entityName) {
          $scope.fieldList[entityName] = $scope.fieldList[entityName] || [];
          $scope.fieldList[entityName].length = 0;
          _.each($scope.meta.fields[$scope.entities[entityName].type], function(field) {
            $scope.fieldList[entityName].push({
              "#tag": "af-field",
              name: field.name,
              defn: _.cloneDeep(_.pick(field, ['title', 'input_type', 'input_attrs']))
            });
          });
        }

        $scope.valuesFields = function() {
          var fields = _.transform($scope.meta.fields[$scope.entities[$scope.selectedEntity].type], function(fields, field) {
            fields.push({id: field.name, text: field.title, disabled: $scope.fieldInUse($scope.selectedEntity, field.name)});
          }, []);
          return {results: fields};
        };

        $scope.removeValue = function(entity, fieldName) {
          delete entity.data[fieldName];
        };

        $scope.$watch('controls.addValue', function(fieldName) {
          if (fieldName) {
            if (!$scope.entities[$scope.selectedEntity].data) {
              $scope.entities[$scope.selectedEntity].data = {};
            }
            $scope.entities[$scope.selectedEntity].data[fieldName] = '';
            $scope.controls.addValue = '';
          }
        });

        // Checks if a field is on the form or set as a value
        $scope.fieldInUse = function(entityName, fieldName) {
          var data = $scope.entities[entityName].data || {},
            found = false;
          if (fieldName in data) {
            return true;
          }
          return check($scope.layout['#children']);
          function check(group) {
            _.each(group, function(item) {
              if (found) {
                return false;
              }
              if (_.isPlainObject(item)) {
                if ((!item['af-fieldset'] || (item['af-fieldset'] === entityName)) && item['#children']) {
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

        function expandFields(collection, entityType) {
          _.each(collection, function (item) {
            if (_.isPlainObject(item)) {
              if (item['af-fieldset']) {
                expandFields(item['#children'], editor.getEntity(item['af-fieldset']).type);
              }
              else if (item['#tag'] === 'af-field') {
                item.defn = item.defn || {};
                _.defaults(item.defn, _.cloneDeep(_.pick(editor.getField(entityType, item.name), ['title', 'input_type', 'input_attrs'])));
              } else {
                expandFields(item['#children'], entityType);
              }
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
        $scope.block = this;
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
          return null;
        };

        $scope.isSelectedFieldset = function(entityName) {
          return entityName === $scope.editor.getSelectedEntity();
        };

        $scope.selectEntity = function() {
          if ($scope.node['af-fieldset']) {
            $scope.editor.selectEntity($scope.node['af-fieldset']);
          }
        };

        $scope.tags = {
          div: ts('Block'),
          fieldset: ts('Fieldset')
        };

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
      require: '^^afGuiEditor',
      link: function($scope, element, attrs, editor) {
        $scope.editor = editor;
      },
      controller: function($scope) {

        $scope.getEntity = function() {
          return $scope.editor.getEntity($scope.entityName);
        };

        $scope.getDefn = function() {
          return $scope.editor.getField($scope.getEntity().type, $scope.node.name);
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
          return _.intersection(splitClass($scope.node['class']), _.keys($scope.alignments))[0];
        };

        $scope.setAlign = function(val) {
          $scope.block.modifyClasses($scope.node, _.keys($scope.alignments), val);
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
      link: function(scope, element, attrs, ngModel) {
        var ts = CRM.ts();

        function read() {
          var htmlVal = element.html();
          if (!htmlVal) {
            htmlVal = ts('Unnamed');
            element.html(htmlVal);
          }
          ngModel.$setViewValue(htmlVal);
        }

        ngModel.$render = function() {
          element.html(ngModel.$viewValue || ' ');
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
            element.html(ngModel.$viewValue || ' ');
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
              var options = _.transform(field.options, function(options, val, key) {
                options.push({id: key, text: val});
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
