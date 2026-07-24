(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskRegisterEvent', function($scope, crmApi4, searchTaskBaseTrait, searchTaskLazyFieldsTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
    const ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait, searchTaskLazyFieldsTrait);

    // Initial task values from hook_civicrm_searchKitTasks (optional pre-configured defaults)
    const values = this.task.values && !Array.isArray(this.task.values) ? this.task.values : {};
    $scope.values = values;

    this.autocompleteParams = {
      fieldName: 'event_id'
    };

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
        // Auto-update role to new event's default if unchanged from previous default
        var defaultRoleId = results.event.length ? results.event[0].default_role_id : null;
        var rolePair = ctrl.values.find(function(p) { return p[0] === 'role_id'; });
        if (rolePair) {
          if (!rolePair[1].length || _.isEqual(rolePair[1], [ctrl._lastDefaultDepValue])) {
            rolePair[1] = defaultRoleId ? [defaultRoleId] : [];
          }
          // Strip role IDs invalid for the new event
          var roleField = ctrl.getField('role_id');
          if (roleField && roleField.options && rolePair[1].length) {
            var validIds = roleField.options.map(function(opt) { return opt.id; });
            rolePair[1] = rolePair[1].filter(function(id) { return validIds.includes(id); });
            if (!rolePair[1].length) {
              rolePair[1] = defaultRoleId ? [defaultRoleId] : [];
            }
          }
        }
        ctrl._lastDefaultDepValue = defaultRoleId;
      },
      onError: function() {
        CRM.alert(ts('Failed to load event fields.'), ts('Error'), 'error');
      }
    });

    this.submit = function() {
      const defaults = _.zipObject(ctrl.values);
      var eventId = ctrl.getFkId(values.event_id);
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

    this.onError = function(error) {
      console.error('Registration failed:', error);
      CRM.alert(ts('An error occurred while attempting to register participants.'), ts('Error'), 'error');
    };

  });
})(angular, CRM.$, CRM._);
