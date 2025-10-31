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
          $scope.data.bundledActions = CRM.vars.crmImportUi.bundledActions;
          $scope.userJob = CRM.vars.crmImportUi.userJob;
          if ($scope.userJob.metadata.bundled_actions === undefined) {
            $scope.userJob.metadata.bundled_actions = [];
          }
          $scope.data.showColumnNames = $scope.userJob.metadata.submitted_values.skipColumnHeader;
          $scope.data.savedMapping = CRM.vars.crmImportUi.savedMapping;
          $scope.isStandalone = CRM.vars.crmImportUi.isStandalone;
          $scope.isTemplate = CRM.vars.crmImportUi.isTemplate;
          $scope.mappingSaving = {updateFieldMapping: 0, newFieldMapping: 0, newFieldMappingName: ''};
          $scope.dateFormats = CRM.vars.crmImportUi.dateFormats;
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
            var selected = (Boolean(entityConfiguration) && Boolean(entityConfiguration[entityMetadata.entity_name])) ? entityConfiguration[entityMetadata.entity_name] : entityMetadata.selected;
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
            if (Boolean(entityMetadata.selected)) {
              entityMetadata.dedupe_rules = $scope.getDedupeRules(selected.contact_type);
            }

            $scope.entitySelection.push({
              id: entityMetadata.entity_name,
              text: entityMetadata.entity_title,
              actions: entityMetadata.actions,
              entity_type: entityMetadata.entity_type,
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
              availableEntity.children = filterEntityFields(entity.entity_type, entity.children, selected, entity.entity_name + '.');
              fields.push(availableEntity);
            }
          });
          return {results: fields};
        });

        $scope.getEntitiesWithBundledActions = function() {
          const entities = [];
          _.each($scope.data.entities, function(entity) {
            if ($scope.data.bundledActions[entity.entity_type]) {
              entities.push({id: entity.id, name: entity.id, text: entity.text});
            }
          });
          return {results : entities};
        };

        $scope.getBundledActionsForEntity = function(entityName) {
          const entityType = $scope.data.entities[entityName].entity_type;
          return function() {
            const actions = [];
            const categorizedActions = {};

            // Group actions by category
            Object.entries($scope.data.bundledActions[entityType]).forEach(([key, action]) => {
              if (!categorizedActions[action.category]) {
                categorizedActions[action.category] = [];
              }
              categorizedActions[action.category].push({
                id: key,
                text: action.label
              });
            });

            // Transform into Select2 format with categories as groups
            Object.entries(categorizedActions).forEach(([category, categoryActions]) => {
              actions.push({
                text: category,
                children: categoryActions
              });
            });

            return {results: actions};
          };
        };

        $scope.getBundledActionConditions = function() {
          const conditions = [
            {id : 'always', text: ts('Always')},
            {id : 'on_multiple_match', text: ts('On multiple match (with first match dedupe rule)')},
          ];
          return {results : conditions};
        };

        $scope.addBundledAction = function(entityType) {
          $scope.userJob.metadata.bundled_actions.push({entity: entityType, action: null, condition: 'always'});
        };

        $scope.removeAction = function(index) {
          $scope.userJob.metadata.bundled_actions.splice(index, 1);
        };

        /**
         * Filter the fields available for the entity based on form selections.
         *
         * Currently we only filter contact fields here, based on contact type, dedupe rule,
         * and action.
         *
         * @type {(function(*=, *=, *=, *=): (*))|*}
         */
        function filterEntityFields(entityType, fields, selection, entityFieldPrefix) {
          if (entityType === 'Contact') {
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
          const contactType = selection.contact_type;
          const action = selection.action;
          const rules = $scope.data.dedupeRules;
          const dedupeRules = Object.keys(rules)
            .filter(key => selection.dedupe_rule.includes(key))
            .map(key => rules[key]);
          fields = fields.filter((function (field) {
            // Using replace here is safe ... for now... cos only soft credits have a prefix
            // but if we add a prefix to contact this will need updating.
            const fieldName = field.id.replace(entityFieldPrefix, '');
            if (action === 'select' && !Boolean(field.match_rule) &&
              (!dedupeRules.length || !dedupeRules.some(rule => Boolean(rule.fields[fieldName])))
            ) {
              // In select mode only fields used to look up the contact are returned.
              return false;
            }
            if (Boolean(contactType)) {
              const supportedTypes = field.contact_type;
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
         * @returns [{}]
         *   e.g [{name: 'IndividualSupervised', 'text' : 'Name and email', 'is_default' : true}]
         */
        $scope.getDedupeRules = function (selectedEntity) {
          const dedupeRules = [
            {contact_type: null, text: ts('Universal'), icon: 'fa-star', children: []},
          ];
          _.each($scope.data.dedupeRules, function (rule) {
            if (!selectedEntity || !rule.contact_type || rule.contact_type === selectedEntity) {
              let optGroup = dedupeRules.find(group => group.contact_type === rule.contact_type);
              if (!optGroup) {
                const contactType = $scope.data.contactTypes.find(type => type.id === rule.contact_type);
                // The contactType might be disabled, ex: Households
                if (!contactType) {
                  return;
                }
                optGroup = {contact_type: rule.contact_type, text: contactType.text, icon: contactType.icon, children: []};
                dedupeRules.push(optGroup);
              }
              optGroup.children.push({id: rule.name, text: rule.title, is_default: rule.used === 'Unsupervised'});
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

            $scope.userJob.metadata.import_mappings.push({
              name: importRow.selectedField,
              default_value: importRow.defaultValue,
              // At this stage column_number is thrown away but we store it here to have it for when we change that.
              column_number: index,
            });
          });
          var userJobs = [];
          if ($scope.mappingSaving.updateFieldMapping || $scope.mappingSaving.newFieldMapping) {
            var templateJob = {
              'is_template': 1,
              'metadata' : $scope.userJob.metadata,
              'job_type' : $scope.userJob.job_type,
              'status_id:name' : 'draft',
              'label' : $scope.userJob.label,
            };
            if ($scope.mappingSaving.newFieldMapping) {
              templateJob.name = 'import_' + $scope.mappingSaving.newFieldMappingName;
              crmApi4('UserJob', 'get', {where: [['name', '=', templateJob.name]]})
                .then(function(result) {
                  if (result.count) {
                    templateJob.name += '_' + new Date().toISOString().replace(/[:.]/g, '-') + '_' + Math.random().toString(36).slice(2, 5);
                  }

                  crmApi4('UserJob', 'save', {records: [templateJob]})
                    .then(function(result) {
                      $scope.userJob.metadata.template_id = result[0].id;
                      userJobs.push($scope.userJob);
                      $scope.saveJobs(userJobs);
                    });
                });
            }
            else {
              templateJob.id = $scope.userJob.metadata.template_id;
              userJobs.push(templateJob);
            }
          }
          if (!$scope.mappingSaving.newFieldMapping) {
            userJobs.push($scope.userJob);
            $scope.saveJobs(userJobs);
          }
        });
        $scope.saveJobs = (function(jobs) {
          crmApi4('UserJob', 'save', {records: jobs})
            .then(function(result) {
                if ($scope.isTemplate) {
                  // Just redirect to the template listing.
                  window.location.href = CRM.url('civicrm/imports/templates');
                }
                else if ($scope.isStandalone) {
                  window.location.href = CRM.url('civicrm/import_preview', {'id' : $scope.userJob.id});
                }
                else {
                  // Only post the form if the save succeeds.
                  document.getElementById("MapField").submit();
                }
              },
              function(failure) {
                if (!$scope.isTemplate) {
                  // @todo add more error handling - for now, at least we waited..
                  document.getElementById("MapField").submit();
                }
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
      entity.selected.dedupe_rule = [];
    });
  });
})(angular, CRM.$, CRM._);
