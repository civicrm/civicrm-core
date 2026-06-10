(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskRegisterEvent', function($scope, crmApi4, searchTaskBaseTrait, searchTaskFieldsTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
    const ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait, searchTaskFieldsTrait);

    const values = this.task.values && !Array.isArray(this.task.values) ? this.task.values : {};
    $scope.values = values;

    this.autocompleteParams = {
      fieldName: 'event_id'
    };

    this.loadFieldsAndValues(this.task, 'Participant', {
      action: 'create',
      where: [['name', 'NOT IN', ['contact_id', 'event_id']]]
    }).then(function() {
      ['role_id', 'status_id', 'source'].forEach(function(fieldName) {
        if (!fieldInUse(fieldName)) {
          ctrl.addField(fieldName);
        }
      });
      const rolePair = ctrl.values.find(function(p) { return p[0] === 'role_id'; });
      if (rolePair && !Array.isArray(rolePair[1])) {
        rolePair[1] = [rolePair[1]];
      }
      if (values.event_id) {
        loadDefaultRole(values.event_id);
      }
    });

    function fieldInUse(fieldName) {
      return ctrl.values.some(function(p) { return p[0] === fieldName; });
    }

    function loadDefaultRole(eventId) {
      if (!eventId) { return; }
      crmApi4('Event', 'get', {
        select: ['default_role_id'],
        where: [['id', '=', eventId]]
      }).then(function(results) {
        if (results.length && results[0].default_role_id) {
          const rolePair = ctrl.values.find(function(p) { return p[0] === 'role_id'; });
          if (rolePair) {
            rolePair[1] = [results[0].default_role_id];
          }
        }
      });
    }

    $scope.$watch('values.event_id', function(newEventId) {
      loadDefaultRole(newEventId);
    });

    this.submit = function() {
      const defaults = _.zipObject(ctrl.values);
      if (values.event_id) {
        defaults.event_id = values.event_id;
      }
      Object.keys(defaults).forEach(function(key) {
        if (defaults[key] === '' || (Array.isArray(defaults[key]) && !defaults[key].length)) {
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
      CRM.alert(ts('Registration failed: ') + error, ts('Error'), 'error');
    };

  });
})(angular, CRM.$, CRM._);
