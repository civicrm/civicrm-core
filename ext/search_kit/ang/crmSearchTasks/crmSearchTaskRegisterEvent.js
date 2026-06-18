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

    var reloading = false;
    var pendingReload = false;
    var lastLoadedRoleId = null;
    var dirtyFields = {};
    var lastDefaultRoleId = null;

    function getEventId() {
      var val = values.event_id;
      return val && _.isObject(val) ? val.id : val;
    }

    function snapshotValues() {
      var snap = {};
      ctrl.values.forEach(function(pair) {
        snap[pair[0]] = pair[1];
      });
      return snap;
    }

    function restoreSnapshot(snap) {
      Object.keys(snap).forEach(function(key) {
        var field = ctrl.getField(key);
        if (field) {
          var existing = ctrl.values.find(function(p) { return p[0] === key; });
          if (existing) {
            existing[1] = snap[key];
          } else {
            ctrl.values.push([key, snap[key]]);
          }
        }
      });
    }

    function addPreselectedFields() {
      ['role_id', 'status_id', 'source'].forEach(function(fieldName) {
        if (ctrl.values.every(function(p) { return p[0] !== fieldName; })) {
          ctrl.addField(fieldName);
        }
      });
      var rolePair = ctrl.values.find(function(p) { return p[0] === 'role_id'; });
      if (rolePair && !Array.isArray(rolePair[1])) {
        rolePair[1] = [rolePair[1]];
      }
    }

    this.markDirty = function(index) {
      if (reloading) { return; }
      var pair = ctrl.values[index];
      if (pair) {
        dirtyFields[pair[0]] = true;
      }
    };

    function loadFields(eventId) {
      dirtyFields = {};
      pendingReload = false;
      reloading = true;
      var snap = snapshotValues();
      if (ctrl.fields.length) {
        ctrl.refreshing = true;
      }
      var getFieldsValues = {event_id: eventId};
      if (snap.role_id && snap.role_id.length) {
        getFieldsValues.role_id = snap.role_id;
      }
      crmApi4({
        getFields: ['Participant', 'getFields', {
          action: 'create',
          select: ['name', 'label', 'description', 'input_type', 'data_type', 'serialize', 'options', 'fk_entity', 'nullable', 'required', 'default_value'],
          loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
          where: [
            ['name', 'NOT IN', ['contact_id', 'event_id']],
            ['deprecated', '=', false],
            ['readonly', '=', false],
          ],
          values: getFieldsValues
        }],
        event: ['Event', 'get', {
          select: ['default_role_id'],
          where: [['id', '=', eventId]],
          limit: 1
        }]
      }).then(function(results) {
        ctrl.fields.length = 0;
        ctrl.values.length = 0;
        ctrl.fields.push(...results.getFields);
        results.getFields.forEach(function(field) {
          if (field.required && !field.default_value) {
            ctrl.addField(field.name);
          }
        });
        restoreSnapshot(snap);
        addPreselectedFields();
        var defaultRoleId = results.event.length ? results.event[0].default_role_id : null;
        var rolePair = ctrl.values.find(function(p) { return p[0] === 'role_id'; });
        if (rolePair) {
          if (!rolePair[1].length || _.isEqual(rolePair[1], [lastDefaultRoleId])) {
            rolePair[1] = defaultRoleId ? [defaultRoleId] : [];
          }
        }
        lastDefaultRoleId = defaultRoleId;
        ctrl.refreshing = false;
      }).then(function() {
        var pair = ctrl.values.find(function(p) { return p[0] === 'role_id'; });
        lastLoadedRoleId = pair ? angular.copy(pair[1]) : null;
        reloading = false;
        if (pendingReload) {
          pendingReload = false;
          loadFields(eventId);
        }
      }).catch(function(error) {
        ctrl.refreshing = false;
        reloading = false;
        console.error('Failed to load event fields:', error);
        CRM.alert(ts('Failed to load event fields.'), ts('Error'), 'error');
      });
    }

    $scope.$watch('values.event_id', function() {
      var id = getEventId();
      if (id) {
        loadFields(id);
      } else {
        ctrl.fields.length = 0;
        ctrl.values.length = 0;
        ctrl.refreshing = false;
        reloading = false;
        pendingReload = false;
        lastLoadedRoleId = null;
        lastDefaultRoleId = null;
      }
    });

    $scope.$watch(function() {
      var pair = ctrl.values.find(function(p) { return p[0] === 'role_id'; });
      return pair ? pair[1] : null;
    }, function(newRole, oldRole) {
      var id = getEventId();
      if (id && Array.isArray(newRole) && newRole.length && !_.isEqual(newRole, lastLoadedRoleId)) {
        if (reloading) {
          pendingReload = true;
        } else {
          loadFields(id);
        }
      }
    }, true);

    this.submit = function() {
      const defaults = _.zipObject(ctrl.values);
      var eventId = getEventId();
      if (eventId) {
        defaults.event_id = eventId;
      }
      Object.keys(defaults).forEach(function(key) {
        var val = defaults[key];
        var empty = val === '' || val === null || val === undefined || (Array.isArray(val) && !val.length);
        if (empty && key === 'role_id') {
          delete defaults[key];
        } else if (empty && !dirtyFields[key]) {
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
