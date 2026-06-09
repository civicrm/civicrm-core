(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskRegisterEvent', function($scope, crmApi4, searchTaskBaseTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
    const ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    const values = this.task.values && !Array.isArray(this.task.values) ? this.task.values : {};
    $scope.values = values;

    if (values.role_id && !Array.isArray(values.role_id)) {
      values.role_id = [values.role_id];
    }

    crmApi4({
      statusField: ['Participant', 'getFields', {
        action: 'create',
        loadOptions: ['id', 'label'],
        where: [['name', '=', 'status_id']]
      }],
      roleField: ['Participant', 'getFields', {
        action: 'create',
        loadOptions: ['id', 'label'],
        where: [['name', '=', 'role_id']]
      }]
    }).then(function(results) {
      ctrl.statusOptions = (results.statusField[0] || {}).options || [];
      ctrl.roleOptions = _.map((results.roleField[0] || {}).options || [], function(opt) {
        return {id: opt.id, text: opt.label};
      });
      if (!values.status_id && ctrl.statusOptions.length) {
        values.status_id = ctrl.statusOptions[0].id;
      }
      ctrl.ready = true;
      if (values.event_id && !values.role_id) {
        loadDefaultRole(values.event_id);
      }
    });

    function loadDefaultRole(eventId) {
      if (!eventId) {
        return;
      }
      crmApi4('Event', 'get', {
        select: ['default_role_id'],
        where: [['id', '=', eventId]]
      }).then(function(results) {
        if (results.length && results[0].default_role_id) {
          values.role_id = [results[0].default_role_id];
        }
      });
    }

    $scope.$watch('values.event_id', function(newEventId) {
      loadDefaultRole(newEventId);
    });

    this.submit = function() {
      ctrl.start({
        defaults: _.cloneDeep(values)
      });
    };

    this.onSuccess = function(result) {
      const registered = result.filter(function(r) {
        return !r.duplicate_id;
      }).length;
      const duplicates = result.filter(function(r) {
        return r.duplicate_id;
      }).length;
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
