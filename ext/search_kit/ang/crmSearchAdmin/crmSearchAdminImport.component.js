(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminImport', {
    templateUrl: '~/crmSearchAdmin/crmSearchAdminImport.html',
    controller: function ($scope, dialogService, crmApi4) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.values = '';

      const checkInput = _.debounce(function() {
        $scope.$apply(function() {
          if (!ctrl.values) {
            ctrl.checking = false;
            return;
          }
          try {
            const apiCalls = JSON.parse(ctrl.values),
              allowedEntities = ['SavedSearch', 'SearchDisplay', 'Group'];
            if ('org.civicrm.afform' in CRM.crmSearchAdmin.modules) {
              allowedEntities.push('Afform');
            }
            // Get entity titles for use in status message
            const getCalls = {
              Entity: ['Entity', 'get', {
                select: ['title', 'title_plural'],
                where: [['name', 'IN', allowedEntities]]
              }, 'name']
            };
            // Get count of existing matches for each import entity
            _.each(apiCalls, function (apiCall) {
              const entity = apiCall[0];
              if (apiCall[1] !== 'save' || ('chain' in apiCall[2] && !_.isEmpty(apiCall[2].chain))) {
                throw ts('Unsupported API action: only "save" is allowed.');
              }
              if (!_.includes(allowedEntities, entity)) {
                throw ts('Unsupported API entity "' + entity + '".');
              }
              if (entity in getCalls) {
                throw ts('Duplicate API entity "' + entity + '".');
              }
              const names = _.map(apiCall[2].records, 'name'),
                where = [['name', 'IN', names]];
              if (entity === 'SearchDisplay') {
                where.push(['saved_search_id.name', '=', apiCall[2].records[0]['saved_search_id.name']]);
              }
              if (names.length) {
                getCalls[entity] = [entity, 'get', {select: ['row_count'], where: where}];
              }
            });
            if (_.keys(getCalls).length < 2) {
              throw ts('No records to import.');
            }
            crmApi4(getCalls)
              .then(function (results) {
                ctrl.checking = false;
                ctrl.error = '';
                ctrl.preview = '';
                _.each(allowedEntities, function (entity) {
                  if (results[entity]) {
                    const info = results.Entity[entity],
                      count = getCalls[entity][2].where[0][2].length,
                      existing = results[entity].count,
                      saveCall = _.findWhere(apiCalls, {0: entity});
                    // Unless it's an afform, the api save params must include `match` or an update is not possible
                    if (existing && entity !== 'Afform' && (!saveCall[2].match || !saveCall[2].match.length)) {
                      ctrl.error += ' ' + ts('Cannot create %1 %2 because an existing one with the same name already exists.', {
                        1: existing,
                        2: existing === 1 ? info.title : info.title_plural
                      });
                      ctrl.error += ' ' + ts('To update existing records, include "match" in the API params.');
                    } else if (existing) {
                      ctrl.preview += ' ' + ts('%1 existing %2 will be updated.', {
                        1: existing,
                        2: existing === 1 ? info.title : info.title_plural
                      });
                    }
                    if (existing < count) {
                      ctrl.preview += ' ' + ts('%1 new %2 will be created.', {
                        1: count - existing,
                        2: (count - existing) === 1 ? info.title : info.title_plural
                      });
                    }
                  }
                });
              }, function (error) {
                ctrl.running = false;
                ctrl.error = error.error_message;
                ctrl.checking = false;
              });
          } catch (e) {
            ctrl.error = e;
            ctrl.checking = false;
          }
        });
      }, 500);

      this.onChangeInput = function() {
        ctrl.checking = true;
        ctrl.error = '';
        ctrl.preview = null;
        checkInput();
      };

      this.run = function() {
        ctrl.running = true;
        ctrl.preview = null;
        const apiCalls = JSON.parse(ctrl.values);
        crmApi4(apiCalls)
          .then(function(result) {
            CRM.alert(
              ts('1 record successfully imported.', {plural: '%count records successfully imported.', count: result.length}),
              ts('Saved'),
              'success'
            );
            // Refresh admin settings (if a db entity was saved the list of entities will be changed)
            fetch(CRM.url('civicrm/ajax/admin/search'))
              .then(response => response.json())
              .then(data => CRM.crmSearchAdmin = data);
            dialogService.close('crmSearchAdminImport');
          }, function(error) {
            ctrl.running = false;
            ctrl.error = ts('Processing Error:') + ' ' + error.error_message;
          });
      };
    }
  });

})(angular, CRM.$, CRM._);
