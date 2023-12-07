(function(angular, $, _) {
  angular.module('exportui', CRM.angRequires('exportui'));

  angular.module('exportui', CRM.angular.modules)

  .component('crmExportUi', {
    templateUrl: '~/exportui/export.html',
    controller: function($scope, $timeout, crmApi, dialogService) {
      var ts = $scope.ts = CRM.ts('exportui'),
        // Which relationships we've already looked up for the preview
        relations = [];

      $scope.option_list = CRM.vars.exportUi.option_list;
      $scope.contact_types = CRM.vars.exportUi.contact_types;
      $scope.location_type_id = [{id: '', text: ts('Primary')}].concat(CRM.vars.exportUi.location_type_id);
      // Map of all fields keyed by name
      $scope.fields = _.transform(CRM.vars.exportUi.fields, function (result, category) {
        _.each(category.children, function (field) {
          result[field.id] = field;
        });
      }, {});
      $scope.data = {
        preview: CRM.vars.exportUi.preview_data,
        contact_type: '',
        columns: []
      };
      // For the "add new field" dropdown
      $scope.new = {col: ''};
      var contactTypes = _.transform($scope.contact_types, function (result, type) {
        result.push(type.id);
        _.each(type.children || [], function (subType) {
          result.push(subType.id);
        });
      });
      var cids = _.filter(_.map(CRM.vars.exportUi.preview_data, 'id'));

      // Get fields for performing the export or saving the field mapping
      function getSelectedColumns() {
        var map = [];
        _.each($scope.data.columns, function (col, no) {
          // Make a copy of col without the extra angular props
          var item = JSON.parse(angular.toJson(col));
          delete item.select;
          delete item.mapping_id;
          item.contact_type = $scope.data.contact_type || 'Contact';
          item.column_number = no;
          map.push(item);
        });
        return map;
      }

      // Load a saved field mapping
      function loadFieldMap(map) {
        $scope.data.columns = [];
        var mapContactTypes = [];
        _.each(map, function (col) {
          if (_.contains(contactTypes, col.contact_type)) {
            mapContactTypes.push(col.contact_type);
          }
          if (col.relationship_type_id && col.relationship_direction) {
            col.select = '' + col.relationship_type_id + '_' + col.relationship_direction;
          } else {
            col.select = col.name;
          }
          $scope.data.columns.push(col);
        });
        // If all the fields are for the same contact type, set it form-wide
        if (!$scope.data.contact_type && _.unique(mapContactTypes).length === 1) {
          $scope.data.contact_type = mapContactTypes[0];
        }
      }

      // Return fields relevant to a contact type
      // Filter out non-contact fields (for relationship selectors)
      function filterFields(contactType, onlyContact) {
        return _.transform(CRM.vars.exportUi.fields, function (result, cat) {
          if (!cat.is_contact && onlyContact) {
            return;
          }
          var fields = _.filter(cat.children, function (field) {
            return !field.contact_type || !contactType || _.contains(field.contact_type, contactType);
          });
          if (fields.length) {
            result.push({
              id: cat.id,
              text: cat.text,
              children: fields
            });
          }
        });
      }

      $scope.getFields = function () {
        return {results: filterFields($scope.data.contact_type)};
      };

      $scope.getRelatedFields = function (contact_type) {
        return function () {
          return {results: filterFields(contact_type, true)};
        };
      };

      $scope.showPreview = function (row, field) {
        var key = field.name;
        if (field.relationship_type_id && field.relationship_direction) {
          fetchRelations(field);
          key = '' + field.relationship_type_id + '_' + field.relationship_direction + '_' + key;
        }
        if (field.location_type_id) {
          key += '_' + field.location_type_id + (field.phone_type_id ? '_' + field.phone_type_id : '');
        }
        return field.name ? row[key] : '';
      };

      function fetchRelations(field) {
        if (cids.length && !relations[field.relationship_type_id + field.relationship_direction]) {
          relations[field.relationship_type_id + field.relationship_direction] = true;
          var a = field.relationship_direction[0],
            b = field.relationship_direction[2],
            params = {
              relationship_type_id: field.relationship_type_id,
              filters: {is_current: 1},
              "api.Contact.getsingle": {id: '$value.contact_id_' + b}
            };
          params['contact_' + a] = {'IN': cids};
          (function (field, params) {
            crmApi('Relationship', 'get', params).then(function (data) {
              _.each(data.values, function (rel) {
                var row = cids.indexOf(rel['contact_id_' + a]);
                if (row > -1) {
                  _.each(rel["api.Contact.getsingle"], function (item, key) {
                    $scope.data.preview[row][field.relationship_type_id + '_' + field.relationship_direction + '_' + key] = item;
                  });
                }
              });
            });
          })(field, params);
        }
      }

      $scope.saveMappingDialog = function () {
        var options = CRM.utils.adjustDialogDefaults({
          width: '40%',
          height: 300,
          autoOpen: false,
          title: ts('Save Fields')
        });
        var mappingNames = _.transform(CRM.vars.exportUi.mapping_names, function (result, n, key) {
          result[key] = n.toLowerCase();
        });
        var model = {
          ts: ts,
          saving: false,
          overwrite: CRM.vars.exportUi.mapping_id ? '1' : '0',
          mapping_id: CRM.vars.exportUi.mapping_id,
          mapping_type_id: CRM.vars.exportUi.mapping_type_id,
          mapping_names: CRM.vars.exportUi.mapping_names,
          new_name: CRM.vars.exportUi.mapping_id ? CRM.vars.exportUi.mapping_names[CRM.vars.exportUi.mapping_id] : '',
          description: CRM.vars.exportUi.mapping_description,
          nameIsUnique: function () {
            return !_.contains(mappingNames, this.new_name.toLowerCase()) || (this.overwrite === '1' && this.new_name.toLowerCase() === this.mapping_names[this.mapping_id].toLowerCase());
          },
          saveMapping: function () {
            this.saving = true;
            var mapping = {
                id: this.overwrite === '1' ? this.mapping_id : null,
                mapping_type_id: this.mapping_type_id,
                name: this.new_name,
                description: this.description,
                sequential: 1
              },
              mappingFields = getSelectedColumns();
            if (!mapping.id) {
              _.each(mappingFields, function (field) {
                delete field.id;
              });
            }
            mapping['api.MappingField.replace'] = {values: mappingFields};
            crmApi('Mapping', 'create', mapping).then(function (result) {
              CRM.vars.exportUi.mapping_id = result.id;
              CRM.vars.exportUi.mapping_description = mapping.description;
              CRM.vars.exportUi.mapping_names[result.id] = mapping.name;
              // Call loadFieldMap to update field ids in $scope.data.columns
              loadFieldMap(result.values[0]['api.MappingField.replace'].values);
              dialogService.close('exportSaveMapping');
            });
          }
        };
        dialogService.open('exportSaveMapping', '~/exportui/exportSaveMapping.html', model, options);
      };

      this.$onInit = function() {
        // Load saved mapping
        if ($('input[name=export_field_map]').val()) {
          loadFieldMap(JSON.parse($('input[name=export_field_map]').val()));
        }

        // Add new col
        $scope.$watch('new.col', function (val) {
          var field = val;
          $timeout(function () {
            if (field) {
              $scope.data.columns.push({
                select: field,
                name: '',
                location_type_id: null,
                phone_type_id: null,
                website_type_id: null,
                im_provider_id: null,
                relationship_type_id: null,
                relationship_direction: null
              });
              $scope.new.col = '';
            }
          });
        });

        // When adding/removing columns
        $scope.$watch('data.columns', function (values) {
          _.each(values, function (col, index) {
            // Remove empty values
            if (!col.select) {
              $scope.data.columns.splice(index, 1);
            } else {
              // Format item
              var selection = $scope.fields[col.select];
              if (selection.relationship_type_id) {
                col.relationship_type_id = selection.relationship_type_id;
                col.relationship_direction = col.select.slice(col.select.indexOf('_') + 1);
              } else {
                col.name = col.select;
                col.relationship_direction = col.relationship_type_id = null;
              }
              var field = col.name ? $scope.fields[col.name] : {};
              col.location_type_id = field.has_location ? col.location_type_id || '' : null;
              _.each($scope.option_list, function (options, list) {
                col[list] = (col.location_type_id || !field.has_location) && field.option_list === list ? col[list] || options[0].id : null;
              });
            }
          });
          // Store data in a quickform hidden field
          var selectedColumns = getSelectedColumns();
          $('input[name=export_field_map]').val(JSON.stringify(selectedColumns));

          // Hide submit button when no fields selected
          $('.crm-button_qf_Map_next').toggle(!!selectedColumns.length);
        }, true);
      };

    }
  });

})(angular, CRM.$, CRM._);
