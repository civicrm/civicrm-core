(function(angular, $, _) {
  // Example usage: <af-form ctrl="afform">
  angular.module('af').component('afForm', {
    bindings: {
      ctrl: '@'
    },
    require: {
      ngForm: 'form'
    },
    controller: function($scope, $element, $timeout, crmApi4, crmStatus, $window, $location, $parse, FileUploader) {
      var schema = {},
        data = {extra: {}},
        status,
        args,
        submissionResponse,
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
      // With no arguments this will prefill the entire form based on url args
      // With selectedEntity, selectedIndex & selectedId provided this will prefill a single entity
      this.loadData = function(selectedEntity, selectedIndex, selectedId) {
        var toLoad = 0,
          params = {name: ctrl.getFormMeta().name, args: {}};
        // Load single entity
        if (selectedEntity) {
          toLoad = selectedId;
          params.fillMode = 'entity';
          params.args[selectedEntity] = {};
          params.args[selectedEntity][selectedIndex] = selectedId;
        }
        // Prefill entire form
        else {
          args = _.assign({}, $scope.$parent.routeParams || {}, $scope.$parent.options || {});
          _.each(schema, function (entity, entityName) {
            if (args[entityName] || entity.actions.update) {
              toLoad++;
            }
            if (args[entityName] && typeof args[entityName] === 'string') {
              args[entityName] = args[entityName].split(',');
            }
          });
          params.args = args;
        }
        if (toLoad) {
          crmApi4('Afform', 'prefill', params)
            .then(function(result) {
              _.each(result, function(item) {
                data[item.name] = data[item.name] || {};
                _.extend(data[item.name], item.values, schema[item.name].data || {});
              });
            });
        }
        // Clear existing contact selection
        else if (selectedEntity) {
          data[selectedEntity][selectedIndex].fields = {};
          if (data[selectedEntity][selectedIndex].joins) {
            data[selectedEntity][selectedIndex].joins = {};
          }
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
          var url = replaceTokens(metaData.redirect, submissionResponse[0]);
          if (url.indexOf('civicrm/') === 0) {
            url = CRM.url(url);
          } else if (url.indexOf('/') === 0) {
            url = $location.protocol() + '://' + $location.host() + url;
          }
          $window.location.href = url;
        }
      }

      function replaceTokens(str, vars) {
        function recurse(stack, values) {
          _.each(values, function(value, key) {
            if (_.isArray(value) || _.isPlainObject(value)) {
              recurse(stack.concat([key]), value);
            } else {
              var token = (stack.length ? stack.join('.') + '.' : '') + key;
              str = str.replace(new RegExp(_.escapeRegExp('[' + token + ']'), 'g'), value);
            }
          });
        }
        recurse([], vars);
        return str;
      }

      this.submit = function() {
        if (!ctrl.ngForm.$valid) {
          CRM.alert(ts('Please fill all required fields.'), ts('Form Error'));
          return;
        }
        status = CRM.status({});
        $element.block();

        crmApi4('Afform', 'submit', {
          name: ctrl.getFormMeta().name,
          args: args,
          values: data}
        ).then(function(response) {
          submissionResponse = response;
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
        })
        .catch(function(error) {
          status.resolve();
          status = CRM.status(error.error_message, 'error');
          $element.unblock();
          CRM.alert(error.error_message, ts('Form Error'));
        });
      };
    }
  });
})(angular, CRM.$, CRM._);
