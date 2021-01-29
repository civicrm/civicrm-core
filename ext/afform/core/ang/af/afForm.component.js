(function(angular, $, _) {
  // Example usage: <af-form ctrl="afform">
  angular.module('af').component('afForm', {
    bindings: {
      ctrl: '@'
    },
    controller: function($scope, $routeParams, $timeout, crmApi4, crmStatus) {
      var schema = {},
        data = {},
        ctrl = this;

      this.$onInit = function() {
        // This component has no template. It makes its controller available within it by adding it to the parent scope.
        $scope.$parent[this.ctrl] = this;

        $timeout(ctrl.loadData);
      };

      this.registerEntity = function registerEntity(entity) {
        schema[entity.modelName] = entity;
        data[entity.modelName] = [];
      };
      this.getEntity = function getEntity(name) {
        return schema[name];
      };
      // Returns field values for a given entity
      this.getData = function getData(name) {
        return data[name];
      };
      this.getSchema = function getSchema(name) {
        return schema[name];
      };
      // Returns the 'meta' record ('name', 'description', etc) of the active form.
      this.getFormMeta = function getFormMeta() {
        return $scope.$parent.meta;
      };
      this.loadData = function() {
        var toLoad = 0;
        _.each(schema, function(entity, entityName) {
          if ($routeParams[entityName] || entity.autofill) {
            toLoad++;
          }
        });
        if (toLoad) {
          crmApi4('Afform', 'prefill', {name: ctrl.getFormMeta().name, args: $routeParams})
            .then(function(result) {
              _.each(result, function(item) {
                data[item.name] = data[item.name] || {};
                _.extend(data[item.name], item.values, schema[item.name].data || {});
              });
            });
        }
      };

      this.submit = function submit() {
        var submission = crmApi4('Afform', 'submit', {name: ctrl.getFormMeta().name, args: $routeParams, values: data});
        return crmStatus({start: ts('Saving'), success: ts('Saved')}, submission);
      };
    }
  });
})(angular, CRM.$, CRM._);
