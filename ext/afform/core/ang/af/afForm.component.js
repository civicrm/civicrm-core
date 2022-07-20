(function(angular, $, _) {
  // Example usage: <af-form ctrl="afform">
  angular.module('af').component('afForm', {
    bindings: {
      ctrl: '@'
    },
    controller: function($scope, $element, $timeout, crmApi4, crmStatus, $window, $location, FileUploader) {
      var schema = {},
        data = {},
        status,
        args,
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
        args = _.assign({}, $scope.$parent.routeParams || {}, $scope.$parent.options || {});
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

      // Used when submitting file fields
      this.fileUploader = new FileUploader({
        url: CRM.url('civicrm/ajax/api4/Afform/submitFile'),
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        onCompleteAll: postProcess,
        onBeforeUploadItem: function(item) {
          status.resolve();
          status = CRM.status({start: ts('Uploading %1', {1: item.file.name})});
        }
      });

      // Called after form is submitted and files are uploaded
      function postProcess() {
        var metaData = ctrl.getFormMeta(),
          dialog = $element.closest('.ui-dialog-content');

        $element.trigger('crmFormSuccess', {
          afform: metaData,
          data: data
        });

        status.resolve();
        $element.unblock();

        if (dialog.length) {
          dialog.dialog('close');
        }

        else if (metaData.redirect) {
          var url = metaData.redirect;
          if (url.indexOf('civicrm/') === 0) {
            url = CRM.url(url);
          } else if (url.indexOf('/') === 0) {
            url = $location.protocol() + '://' + $location.host() + url;
          }
          $window.location.href = url;
        }
      }

      this.submit = function() {
        status = CRM.status({});
        $element.block();

        crmApi4('Afform', 'submit', {
          name: ctrl.getFormMeta().name,
          args: args,
          values: data}
        ).then(function(response) {
          if (ctrl.fileUploader.getNotUploadedItems().length) {
            _.each(ctrl.fileUploader.getNotUploadedItems(), function(file) {
              file.formData.push({
                params: JSON.stringify(_.extend({
                  token: response[0].token,
                  name: ctrl.getFormMeta().name
                }, file.crmApiParams()))
              });
            });
            ctrl.fileUploader.uploadAll();
          } else {
            postProcess();
          }
        });
      };
    }
  });
})(angular, CRM.$, CRM._);
