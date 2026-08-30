(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskRegisterEvent', function($scope, searchTaskBaseTrait, searchTaskLazyFieldsTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
    const ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait, searchTaskLazyFieldsTrait);

    ctrl._lastDefaultRoleId = null;

    // Initial task values from hook_civicrm_searchKitTasks (optional pre-configured defaults)
    const values = this.task.values && !Array.isArray(this.task.values) ? this.task.values : {};
    $scope.values = values;

    ctrl.setupLazyFields($scope, {
      fkField: 'event_id',
      depField: 'role_id',
      entityName: 'Participant',
      presetFields: ['role_id', 'status_id', 'source'],
      // Fetch event's default_role_id to auto-update role when switching events
      getAdditionalApiCalls: function(eventId) {
        return {
          event: ['Event', 'get', {
            select: ['default_role_id'],
            where: [['id', '=', eventId]],
            limit: 1
          }]
        };
      },
      onFieldsLoaded: function(results) {
        var defaultRoleId = results.event.length ? results.event[0].default_role_id : null;
        var newRole = ctrl.getFieldValue('role_id');
        if (newRole) {
          if (!newRole.length || _.isEqual(newRole, [ctrl._lastDefaultRoleId])) {
            newRole = defaultRoleId ? [defaultRoleId] : [];
          }
          // Strip role IDs invalid for the new event
          var roleField = ctrl.getField('role_id');
          if (roleField && roleField.options && newRole.length) {
            var validIds = roleField.options.map(function(opt) { return opt.id; });
            newRole = newRole.filter(function(id) { return validIds.includes(id); });
            if (!newRole.length) {
              newRole = defaultRoleId ? [defaultRoleId] : [];
            }
          }
          ctrl.setFieldValue('role_id', newRole);
        }
        ctrl._lastDefaultRoleId = defaultRoleId;
      },
      onClear: function() {
        ctrl._lastDefaultRoleId = null;
      },
      // Config onError (field load failure)
      onError: function(error) {
        CRM.alert(ts('Failed to load event fields.'), ts('Error'), 'error');
      }
    });

    this.submit = function() {
      const defaults = _.zipObject(ctrl.values);
      var eventId = values.event_id;
      if (eventId) {
        defaults.event_id = eventId;
      }
      // Strip empty role_id and source so BAO defaults apply (e.g. event's default_role_id)
      Object.keys(defaults).forEach(function(key) {
        var val = defaults[key];
        var empty = val === '' || val === null || val === undefined || (Array.isArray(val) && !val.length);
        if (empty && (key === 'role_id' || key === 'source')) {
          delete defaults[key];
        }
      });
      ctrl.start({defaults: defaults, match: ['contact_id', 'event_id']});
    };

    this.onSuccess = function(result) {
      const registered = result.filter(function(r) { return !r.duplicate_id; }).length;
      const duplicates = result.filter(function(r) { return r.duplicate_id; }).length;
      let msg = ts('%count participant(s) registered', {count: registered});
      if (duplicates) {
        msg += '<br>' + ts('%count already registered', {count: duplicates});
      }
      CRM.alert(msg, ts('Success'), 'success');
      this.close(result);
    };

    // Batch runner onError (save failure)
    this.onError = function(error) {
      console.error('Registration failed:', error);
      CRM.alert(ts('An error occurred while attempting to register participants.'), ts('Error'), 'error');
      this.cancel();
    };

  });
})(angular, CRM.$, CRM._);
