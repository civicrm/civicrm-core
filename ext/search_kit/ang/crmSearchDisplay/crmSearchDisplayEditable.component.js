// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  var optionsCache = {};

  angular.module('crmSearchDisplay').component('crmSearchDisplayEditable', {
    bindings: {
      row: '<',
      col: '<',
      cancel: '&',
      onSuccess: '&'
    },
    templateUrl: '~/crmSearchDisplay/crmSearchDisplayEditable.html',
    controller: function($scope, $element, crmApi4, crmStatus) {
      var ctrl = this,
        initialValue,
        col;

      this.$onInit = function() {
        col = this.col;
        this.value = _.cloneDeep(this.row[col.editable.value].raw);
        initialValue = _.cloneDeep(this.row[col.editable.value].raw);

        this.field = {
          data_type: col.editable.data_type,
          input_type: col.editable.input_type,
          name: col.editable.name,
          options: col.editable.options,
          fk_entity: col.editable.fk_entity,
          serialize: col.editable.serialize,
        };

        $(document).on('keydown.crmSearchDisplayEditable', function(e) {
          if (e.key === 'Escape') {
            $scope.$apply(function() {
              ctrl.cancel();
            });
          } else if (e.key === 'Enter') {
            $scope.$apply(ctrl.save);
          }
        });

        if (this.field.options === true) {
          loadOptions();
        }
      };

      this.$onDestroy = function() {
        $(document).off('.crmSearchDisplayEditable');
      };

      this.save = function() {
        if (ctrl.value === initialValue) {
          ctrl.cancel();
          return;
        }
        var values = {id: ctrl.row[col.editable.id].raw};
        values[col.editable.name] = ctrl.value;
        $('input', $element).attr('disabled', true);
        crmStatus({}, crmApi4(col.editable.entity, 'update', {
          values: values
        })).then(ctrl.onSuccess);
      };

      function loadOptions() {
        var cacheKey = col.editable.entity + ' ' + ctrl.field.name;
        if (optionsCache[cacheKey]) {
          ctrl.field.options = optionsCache[cacheKey];
          return;
        }
        crmApi4(col.editable.entity, 'getFields', {
          action: 'update',
          select: ['options'],
          loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
          where: [['name', '=', ctrl.field.name]],
        }, 0).then(function(field) {
          ctrl.field.options = optionsCache[cacheKey] = field.options;
        });
      }
    }
  });

})(angular, CRM.$, CRM._);
