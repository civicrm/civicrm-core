(function(angular, $, _) {
  angular.module('afGuiEditor', CRM.angRequires('afGuiEditor'));

  angular.module('afGuiEditor').directive('afGuiEditor', function(crmApi4) {
    return {
      restrict: 'A',
      //require: 'ngModel',
      templateUrl: '~/afGuiEditor/main.html',
      scope: {
        afGuiEditor: '='
      },
      link: function($scope, $el, $attr) {
        $scope.ts = CRM.ts();
        $scope.afform = null;
        $scope.selectedEntity = null;
        $scope.meta = CRM.afformAdminData;
        $scope.controls = {};
        var newForm = {
          title: ts('Untitled Form'),
          layout: {
            '#tag': 'af-form',
            ctrl: 'modelListCtrl',
            '#children': [
              {
                '#tag': 'af-entity',
                type: 'Contact',
                data: {
                  contact_type: 'Individual'
                },
                name: 'Contact1',
                label: 'Contact 1',
                'url-autofill': '1',
                autofill: 'user'
              }
            ]
          }
        };
        if ($scope.afGuiEditor.name) {
          crmApi4('Afform', 'get', {where: [['name', '=', $scope.afGuiEditor.name]]}, 0)
            .then(initialize);
        }
        else {
          initialize(newForm);
        }

        function initialize(afform) {
          // Todo - show error msg if form is not found
          $scope.afform = afform;
          $scope.entities = getTags(afform.layout, 'af-entity', 'name');
        }

        $scope.addEntity = function(entityType) {
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
          $scope.afform.layout['#children'].push($scope.entities[entityType + num]);
          $scope.selectEntity(entityType + num);
        };

        $scope.removeEntity = function(entityName) {
          delete $scope.entities[entityName];
          $scope.afform.layout['#children'].splice(_.findIndex($scope.afform.layout['#children'], {'#tag': 'af-entity', name: entityName}), 1);
          $scope.selectEntity(null);
        };

        $scope.selectEntity = function(entityName) {
          $scope.selectedEntity = entityName;
        };

        $scope.getField = function(entityName, fieldName) {
          return _.filter($scope.meta.fields[entityName], {name: fieldName})[0];
        };

        $scope.valuesFields = function() {
          var fields = _.transform($scope.meta.fields[$scope.entities[$scope.selectedEntity].type], function(fields, field) {
            fields.push({id: field.name, text: field.title, disabled: field.name in $scope.entities[$scope.selectedEntity].data});
          }, []);
          return {results: fields};
        };

        $scope.removeValue = function(entity, fieldName) {
          delete entity.data[fieldName];
        };

        $scope.$watch('controls.addValue', function(fieldName) {
          if (fieldName) {
            $scope.entities[$scope.selectedEntity].data[fieldName] = '';
            $scope.controls.addValue = '';
          }
        });

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
