// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  var optionsCache = {};

  angular.module('crmSearchDisplay').component('crmSearchDisplayEditable', {
    bindings: {
      row: '<',
      col: '<',
      cancel: '&',
      doSave: '&'
    },
    templateUrl: '~/crmSearchDisplay/crmSearchDisplayEditable.html',
    controller: function($scope, $element, crmApi4) {
      var ctrl = this,
        initialValue,
        col;

      this.$onInit = function() {
        col = this.col;
        this.value = _.cloneDeep(col.edit.value);
        initialValue = _.cloneDeep(col.edit.value);

        this.field = {
          data_type: col.edit.data_type,
          input_type: col.edit.input_type,
          name: col.edit.value_key,
          options: col.edit.options,
          fk_entity: col.edit.fk_entity,
          serialize: col.edit.serialize,
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
        var record = _.cloneDeep(col.edit.record);
        record[col.edit.value_key] = ctrl.value;
        $('input', $element).attr('disabled', true);
        ctrl.doSave({apiCall: [col.edit.entity, 'update', {values: record}]});
      };

      function loadOptions() {
        var cacheKey = col.edit.entity + ' ' + ctrl.field.name;
        if (optionsCache[cacheKey]) {
          ctrl.field.options = optionsCache[cacheKey];
          return;
        }
        crmApi4(col.edit.entity, 'getFields', {
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
