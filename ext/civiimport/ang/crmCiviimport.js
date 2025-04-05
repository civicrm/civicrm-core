(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('crmCiviimport', CRM.angRequires('crmCiviimport'));

  angular.module('crmCiviimport').component('crmImportUi', {
      templateUrl: '~/crmCiviimport/Import.html',
      controller: function($scope, crmApi4, crmStatus, crmUiHelp) {

        // The ts() and hs() functions help load strings for this module.
        var ts = $scope.ts = CRM.ts('civiimport');
        var hs = $scope.hs = crmUiHelp({file: 'CRM/crmCiviimport/crmImportUi'});
        // Local variable for this controller (needed when inside a callback fn where `this` is not available).
        var ctrl = this;

        $scope.load = (function () {
          // The components of crmImportUi that we use are assigned individually for clarity - but
          // don't seem to work without the first assignment....
          $scope.data = CRM.vars.crmImportUi;
          $scope.data.rows = CRM.vars.crmImportUi.rows;
          $scope.data.entityMetadata = CRM.vars.crmImportUi.entityMetadata;
          // The defaults here are derived in the php layer from the saved mapping or the column
          // headers. The latter involves some regex.
          $scope.data.defaults = CRM.vars.crmImportUi.defaults;
          $scope.userJob = CRM.vars.crmImportUi.userJob;
          $scope.data.showColumnNames = $scope.userJob.metadata.submitted_values.skipColumnHeader;
          $scope.data.savedMapping = CRM.vars.crmImportUi.savedMapping;
          $scope.mappingSaving = {updateFieldMapping: 0, newFieldMapping: 0};
          // Used for dedupe rules select options, also for filtering available fields.
          $scope.data.dedupeRules = CRM.vars.crmImportUi.dedupeRules;
          // Used for select contact type select-options.
          $scope.data.contactTypes = CRM.vars.crmImportUi.contactTypes;
          // The headers from the data-source + any previously added user-defined rows.
          $scope.data.columnHeaders = CRM.vars.crmImportUi.columnHeaders;
          $scope.data.entities = {};
          // Available entities is entityMetadata mapped to a form-friendly format
          $scope.entitySelection = [];
          var entityConfiguration = $scope.userJob.metadata.entity_configuration;
          _.each($scope.data.entityMetadata, function (entityMetadata) {
            var selected = Boolean(entityConfiguration) ? entityConfiguration[entityMetadata.entity_name] : entityMetadata.selected;
            // If our selected action is not available then fall back to the entity default.
            // This would happen if we went back to the DataSource screen & made a change, as the
            // php layer filters on that configuration options
            var isActionValid = entityMetadata.actions.filter((function (action) {
              if (action.id === selected.action) {
                return true;
              }
            }));
            if (isActionValid.length === 0) {
              // Selected action not available, go back to the default.
              selected.action = entityMetadata.selected.action;
            }

            entityMetadata.dedupe_rules = [];
            if (Boolean(entityMetadata.selected) && Boolean(selected.contact_type)) {
              entityMetadata.dedupe_rules = $scope.getDedupeRules(selected.contact_type);
            }

            $scope.entitySelection.push({
              id: entityMetadata.entity_name,
              text: entityMetadata.entity_title,
              actions: entityMetadata.actions,
              is_contact: Boolean(entityMetadata.is_contact),
              entity_data: entityMetadata.entity_data,
              dedupe_rules: entityMetadata.dedupe_rules,
            });
            $scope.addEntity(entityMetadata.entity_name, selected);
          });

          function buildImportMappings() {
            $scope.data.importMappings = [];
            var importMappings = $scope.userJob.metadata.import_mappings;
            _.each($scope.data.columnHeaders, function (header, index) {
              var fieldName = $scope.data.defaults['mapper[' + index + ']'][0];
              if (Boolean(fieldName)) {
                fieldName = fieldName.replace('__', '.');
              }
              var fieldDefault = null;

              if (Boolean(importMappings) && importMappings.hasOwnProperty(index)) {
                // If this form has already been used for the job, load from what it saved.
                // Note we also checked the importMapping was defined. This would be FALSE
                // if a csv is being imported with more fields than the are in the original
                // mapping. We check for that so it will skip gracefully.
                // (The user will see a warning.)
                fieldName = importMappings[index].name;
                fieldDefault = importMappings[index].default_value;
              }
              $scope.data.importMappings.push({
                header: header,
                selectedField: fieldName,
                defaultValue: fieldDefault
              });
            });
          }

          buildImportMappings();

        });

        /**
         * Get fields available to map to.
         *
         * @type {function(): {results: $scope.data.entityMetadata}}
         */
        $scope.getFields = (function () {
          var fields = [];
          // The $scope.data.entityMetadata entity array has all available fields.
          // - for field filtering we have to start with the full array or it just gets smaller & smaller.
          _.each($scope.data.entityMetadata, function (entity) {
            // The $scope.data.entities has the selected data (but the fields are already filtered)
            var selected = $scope.data.entities[entity.entity_name].selected;
            if (selected.action !== 'ignore') {
              availableEntity = _.clone(entity);
              availableEntity.children = filterEntityFields(entity.is_contact, entity.children, selected, entity.entity_name + '.');
              fields.push(availableEntity);
            }
          });
          return {results: fields};
        });

        /**
         * Filter the fields available for the entity based on form selections.
         *
         * Currently we only filter contact fields here, based on contact type, dedupe rule,
         * and action.
         *
         * @type {(function(*=, *=, *=, *=): (*))|*}
         */
        function filterEntityFields(isContact, fields, selection, entityFieldPrefix) {
          if (isContact) {
            return filterContactFields(fields, selection, entityFieldPrefix);
          }
          return fields;
        }

        /**
         * Filter contact fields, removing fields not appropriate for the entity or action.
         *
         * @type {function(*=, *): *}
         */
        function filterContactFields(fields, selection, entityFieldPrefix) {
          var contactType = selection.contact_type;
          var action = selection.action;
          var rules = $scope.data.dedupeRules;
          var dedupeRule = rules[selection.dedupe_rule];
          fields = fields.filter((function (field) {
            // Using replace here is safe ... for now... cos only soft credits have a prefix
            // but if we add a prefix to contact this will need updating.
            var fieldName = field.id.replace(entityFieldPrefix, '');
            if (action === 'select' && !Boolean(field.match_rule) &&
              (!Boolean(dedupeRule) || !Boolean(dedupeRule.fields[fieldName]))
            ) {
              // In select mode only fields used to look up the contact are returned.
              return false;
            }
            if (Boolean(contactType)) {
              var supportedTypes = field.contact_type;
              return supportedTypes[contactType];
            }
            // No contact type specified, do not filter on it.
            return true;

          }));
          return fields;
        }

        /**
         * Add the entity to the selected scope.
         */
        $scope.addEntity = function (selectedEntity, selected) {
          if ($scope.data.entities[selectedEntity] === undefined) {
            var entityData = $scope.getEntityMetadata(selectedEntity);
            entityData.selected = selected;
            if (entityData.id !== undefined) {
              $scope.data.entities[selectedEntity] = entityData;
            }
          }
        };

        /**
         * Get metadata for the given entity.
         *
         * @param selectedEntity
         * @returns {*[]}
         */
        $scope.getEntityMetadata = function (selectedEntity) {
          var entityData = {};
          _.each($scope.entitySelection, function (entityDetails) {
            if (entityDetails.id === selectedEntity) {

              entityData = entityDetails;
              return false;
            }
          });
          return entityData;
        };

        /**
         * Get a list of dedupe rules for the entity type.
         *
         * @param selectedEntity
         * @returns {{}}
         *   e.g {{name: 'IndividualSupervised', 'text' : 'Name and email', 'is_default' : true}}
         */
        $scope.getDedupeRules = function (selectedEntity) {
          var dedupeRules = [];
          _.each($scope.data.dedupeRules, function (rule) {
            if (rule.contact_type === selectedEntity) {
              dedupeRules.push({'id': rule.name, 'text': rule.title, 'is_default': rule.used === 'Unsupervised'});
            }
          });
          return dedupeRules;
        };

        /**
         * Get the entity for the given field.
         *
         * @type {$scope.getEntityForField}
         */
        $scope.getEntityForField = (function (fieldName) {
          var entityName = '';
          _.each($scope.data.entityMetadata, function (fields) {
            _.each(fields.children, function (field) {
              if (field.id === fieldName) {
                entityName = fields.entity_name;
                return false;
              }
            });
          });
          return entityName;
        });

        $scope.toggleMappingFields = (function (fieldName, extra) {
          if (fieldName === 'updateFieldMapping' && $scope.mappingSaving.updateFieldMapping === 0) {
            $scope.mappingSaving.newFieldMapping = 0;
          }
          if (fieldName === 'newFieldMapping' && $scope.mappingSaving.newFieldMapping === 0) {
            $scope.mappingSaving.updateFieldMapping = 0;
          }
        });

        /**
         * Add another row to the mapping.
         *
         * This row will use a default value and be the same for all rows imported.
         *
         * @type {$scope.addRow}
         */
        $scope.addRow = (function () {
          $scope.data.importMappings.push({'header' : '', 'selectedField' : undefined});
          $scope.userJob.metadata.DataSource.column_headers.push('');
        });

        $scope.alterRow = (function (index, row) {
          if (row.header === '' && row.selectedField === '') {
            // Deleting a mapped row.
            $scope.data.importMappings.splice(index, 1);
            $scope.userJob.metadata.DataSource.column_headers.splice(index, 1);
          }
        });

        /**
         * Save the user job configuration on save.
         *
         * We add two arrays to the 'metadata' key. This is in the format returned from `Parser->getFieldMappings()`
         * and is combined with quick form data in that function. In addition to the values permitted by
         * the quickForm 'default_value' is supported.
         * - import mappings. e.g
         *   ['name' => 'financial_type_id', default_value' => 'Cash'],
         *   ['name' => 'soft_credit.contact.external_identifier', 'default_value' => '', 'entity_data' => ['soft_credit' => ['soft_credit_type_id => 7]],
         *   ...
         * - entity_configuration
         *
         * @type {$scope.save}
         */
        $scope.save = (function ($event) {
          $event.preventDefault();
          $scope.userJob.metadata.entity_configuration = {};
          $scope.userJob.metadata.import_mappings = [];
          _.each($scope.entitySelection, function (entity) {
            $scope.userJob.metadata.entity_configuration[entity.id] = entity.selected;
          });
          _.each($scope.data.importMappings, function (importRow, index) {
            selectedEntity = $scope.getEntityForField(importRow.selectedField);
            var entityConfig = {};
            if (selectedEntity === 'SoftCreditContact') {
              // For now we just hard-code this - mapping to soft_credit a bit undefined - but
              // we are mimicking getMappingFieldFromMapperInput on the php layer.
              // Could get it from entity_data but .... later.
              entityConfig = {'soft_credit': $scope.userJob.metadata.entity_configuration[selectedEntity]};
            }

            $scope.userJob.metadata.import_mappings.push({
              name: importRow.selectedField,
              default_value: importRow.defaultValue,
              // At this stage column_number is thrown away but we store it here to have it for when we change that.
              column_number: index,
              entity_data: entityConfig
            });
          });
          crmApi4('UserJob', 'save', {records: [$scope.userJob]})
            .then(function(result) {
              // Only post the form if the save succeeds.
              document.getElementById("MapField").submit();
            },
            function(failure) {
              // @todo add more error handling - for now, at least we waited...
              document.getElementById("MapField").submit();
            }
          );
        });

        $scope.load();
      }
    }
  );

  /**
   * This component is for the specific entity within the entity ng-repeat.
   */
  angular.module('crmCiviimport').controller('crmImportUiEntity', function($scope) {
    /**
     * Get the available dedupe rules.
     *
     * @type {function(*): []|*}
     */
    $scope.getDedupeRule = (function() {
      return {results: $scope.entity.dedupe_rules};
    });

    /**
     * Update the metadata module after a change.
     *
     * @type {$scope.updateContactType}
     */
    $scope.updateContactType = (function(entity) {
      entity.dedupe_rules = $scope.getDedupeRules(entity.selected.contact_type);
      entity.selected.dedupe_rule = entity.dedupe_rules[0].id;
    });
  });
})(angular, CRM.$, CRM._);
