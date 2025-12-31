(function(angular, $, _) {
  "use strict";

  let paths = {};

  // Trait shared by task controllers
  angular.module('crmSearchTasks').factory('searchTaskFieldsTrait', function(crmApi4, $timeout) {
    // Trait properties get mixed into task controller using angular.extend()
    const ctrl = {

      values: [],
      fields: [],

      loadFieldsAndValues: function(task, entityName, getFieldsParams, apiCalls) {
        this.fields.length = 0;
        this.values.length = 0;
        // Values are initially empty unless `hook_civicrm_searchKitTasks` has set them
        if (task.values && !Array.isArray(task.values)) {
          Object.keys(task.values).forEach(key => this.values.push([key, task.values[key]]));
        }

        apiCalls = apiCalls || {};
        getFieldsParams = _.merge({
            action: 'update',
            select: ['name', 'label', 'description', 'input_type', 'data_type', 'serialize', 'options', 'fk_entity', 'nullable', 'required', 'default_value'],
            loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
            where: [],
          },
          getFieldsParams || {}
        );
        getFieldsParams.where.push(['deprecated', '=', false], ['readonly', '=', false]);
        apiCalls.getFields = [entityName, 'getFields', getFieldsParams];

        // Info used by getUrl function
        apiCalls.entityInfo = ['Entity', 'get', {
          where: [['name', '=', entityName]],
          select: ['paths']
        }, 0];

        const apiResult = crmApi4(apiCalls);
        apiResult.then((results) => {
          ctrl.fields.push(... results.getFields);
          paths = results.entityInfo.paths;
          results.getFields.forEach(field => {
            if (field.required && !field.default_value) {
              this.addField(field.name);
            }
          });
        });
        return apiResult;
      },

      getField: function(fieldName) {
        return ctrl.fields.find(field => field.name === fieldName);
      },

      addField: function(selection) {
        if (selection && !fieldInUse(selection)) {
          const field = this.getField(selection);
          let value = '';
          if (field.serialize || field.data_type === 'Array') {
            value = [];
          } else if (field.data_type === 'Boolean') {
            value = true;
          } else if (field.default_value) {
            value = field.default_value;
          } else if (field.options && field.options.length) {
            value = field.options[0].id;
          }
          this.values.push([selection, value]);
        }
      },

      updateField: function(index) {
        // Debounce the onchange event using timeout
        $timeout(() => {
          if (!this.values[index][0]) {
            this.values.splice(index, 1);
          }
        });
      },

      availableFields: function() {
        const results = ctrl.fields.map(item => {
          const formatted = {
            id: item.name,
            text: item.label,
            description: item.description
          };
          if (fieldInUse(item.name)) {
            formatted.disabled = true;
          }
          return formatted;
        });
        return {results: results};
      },

      getUrl(action, values) {
        const path = paths[action];
        if (!path) {
          return null;
        }
        return CRM.url(path.replace(/\[(.*?)]/g, (match, key) => values[key]));
      }

    };

    function fieldInUse(fieldName) {
      return ctrl.values.map(value => value[0]).includes(fieldName);
    }

    return ctrl;
  });

})(angular, CRM.$, CRM._);
