(function(angular, $, _) {
  // Example usage: <af-form ctrl="afform">
  angular.module('af').component('afForm', {
    bindings: {
      ctrl: '@'
    },
    controller: function($scope, $timeout, crmApi4, crmStatus, $window, $location) {
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
        var toLoad = 0,
          args = $scope.$parent.routeParams || {};
        _.each(schema, function(entity, entityName) {
          if (args[entityName] || entity.autofill) {
            toLoad++;
          }
        });
        if (toLoad) {
          crmApi4('Afform', 'prefill', {name: ctrl.getFormMeta().name, args: args})
            .then(function(result) {
              _.each(result, function(item) {
                data[item.name] = data[item.name] || {};
                _.extend(data[item.name], item.values, schema[item.name].data || {});
              });
            });
        }
      };

      this.submit = function submit() {
        var submission = crmApi4('Afform', 'submit', {name: ctrl.getFormMeta().name, args: $scope.$parent.routeParams || {}, values: data});
        var metaData = ctrl.getFormMeta();
        if (metaData.redirect) {
          submission.then(function() {
            var url = metaData.redirect;
            if (url.indexOf('civicrm/') === 0) {
              url = CRM.url(url);
            } else if (url.indexOf('/') === 0) {
              url = $location.protocol() + '://' + $location.host() + url;
            }
            $window.location.href = url;
          });
        }
        return crmStatus({start: ts('Saving'), success: ts('Saved')}, submission);
      };
    }
  });
})(angular, CRM.$, CRM._);
