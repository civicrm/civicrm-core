(function(angular, $, _) {

  var crmCaseType = angular.module('crmCaseType', ['ngRoute', 'ui.utils', 'crmUi', 'unsavedChanges', 'crmUtil']);

  // Note: This template will be passed to cloneDeep(), so don't put any funny stuff in here!
  var newCaseTypeTemplate = {
    title: "",
    name: "",
    is_active: "1",
    weight: "1",
    definition: {
      activityTypes: [
        {name: 'Open Case', max_instances: 1},
        {name: 'Email'},
        {name: 'Follow up'},
        {name: 'Meeting'},
        {name: 'Phone Call'}
      ],
      activitySets: [
        {
          name: 'standard_timeline',
          label: 'Standard Timeline',
          timeline: '1', // Angular won't bind checkbox correctly with numeric 1
          activityTypes: [
            {name: 'Open Case', status: 'Completed' }
          ]
        }
      ],
      caseRoles: [
        { name: 'Case Coordinator', creator: '1', manager: '1'}
      ]
    }
  };

  crmCaseType.config(['$routeProvider',
    function($routeProvider) {
      $routeProvider.when('/caseType', {
        templateUrl: '~/crmCaseType/list.html',
        controller: 'CaseTypeListCtrl',
        resolve: {
          caseTypes: function($route, crmApi) {
            return crmApi('CaseType', 'get', {options: {limit: 0}});
          }
        }
      });
      $routeProvider.when('/caseType/:id', {
        templateUrl: '~/crmCaseType/edit.html',
        controller: 'CaseTypeCtrl',
        resolve: {
          apiCalls: function($route, crmApi) {
            var reqs = {};
            reqs.actStatuses = ['OptionValue', 'get', {
              option_group_id: 'activity_status',
              sequential: 1,
              options: {limit: 0}
            }];
            reqs.caseStatuses = ['OptionValue', 'get', {
              option_group_id: 'case_status',
              sequential: 1,
              options: {limit: 0}
            }];
            reqs.actTypes = ['OptionValue', 'get', {
              option_group_id: 'activity_type',
              sequential: 1,
              options: {
                sort: 'name',
                limit: 0
              }
            }];
            reqs.relTypes = ['RelationshipType', 'get', {
              sequential: 1,
              options: {
                sort: CRM.crmCaseType.REL_TYPE_CNAME,
                limit: 0
              }
            }];
            if ($route.current.params.id !== 'new') {
              reqs.caseType = ['CaseType', 'getsingle', {
                id: $route.current.params.id
              }];
            }
            return crmApi(reqs);
          }
        }
      });
    }
  ]);

  // Add a new record by name.
  // Ex: <crmAddName crm-options="['Alpha','Beta','Gamma']" crm-var="newItem" crm-on-add="callMyCreateFunction(newItem)" />
  crmCaseType.directive('crmAddName', function() {
    return {
      restrict: 'AE',
      template: '<input class="add-activity crm-action-menu fa-plus" type="hidden" />',
      link: function(scope, element, attrs) {

        var input = $('input', element);

        scope._resetSelection = function() {
          $(input).select2('close');
          $(input).select2('val', '');
          scope[attrs.crmVar] = '';
        };

        $(input).crmSelect2({
          data: scope[attrs.crmOptions],
          createSearchChoice: function(term) {
            return {id: term, text: term + ' (' + ts('new') + ')'};
          },
          createSearchChoicePosition: 'bottom',
          placeholder: attrs.placeholder
        });
        $(input).on('select2-selecting', function(e) {
          scope[attrs.crmVar] = e.val;
          scope.$evalAsync(attrs.crmOnAdd);
          scope.$evalAsync('_resetSelection()');
          e.preventDefault();
        });

        scope.$watch(attrs.crmOptions, function(value) {
          $(input).select2('data', scope[attrs.crmOptions]);
          $(input).select2('val', '');
        });
      }
    };
  });

  crmCaseType.controller('CaseTypeCtrl', function($scope, crmApi, apiCalls) {
    // CRM_Case_XMLProcessor::REL_TYPE_CNAME
    var REL_TYPE_CNAME = CRM.crmCaseType.REL_TYPE_CNAME,

    ts = $scope.ts = CRM.ts(null);

    $scope.activityStatuses = apiCalls.actStatuses.values;
    $scope.caseStatuses = _.indexBy(apiCalls.caseStatuses.values, 'name');
    $scope.activityTypes = _.indexBy(apiCalls.actTypes.values, 'name');
    $scope.activityTypeOptions = _.map(apiCalls.actTypes.values, formatActivityTypeOption);
    $scope.relationshipTypeOptions = _.map(apiCalls.relTypes.values, function(type) {
      return {id: type[REL_TYPE_CNAME], text: type.label_b_a};
    });
    $scope.locks = {caseTypeName: true, activitySetName: true};

    $scope.workflows = {
      'timeline': 'Timeline',
      'sequence': 'Sequence'
    };

    $scope.caseType = apiCalls.caseType ? apiCalls.caseType : _.cloneDeep(newCaseTypeTemplate);
    $scope.caseType.definition = $scope.caseType.definition || [];
    $scope.caseType.definition.activityTypes = $scope.caseType.definition.activityTypes || [];
    $scope.caseType.definition.activitySets = $scope.caseType.definition.activitySets || [];
    $scope.caseType.definition.caseRoles = $scope.caseType.definition.caseRoles || [];
    $scope.caseType.definition.statuses = $scope.caseType.definition.statuses || [];

    $scope.selectedStatuses = {};
    _.each(apiCalls.caseStatuses.values, function (status) {
      $scope.selectedStatuses[status.name] = !$scope.caseType.definition.statuses.length || $scope.caseType.definition.statuses.indexOf(status.name) > -1;
    });

    $scope.addActivitySet = function(workflow) {
      var activitySet = {};
      activitySet[workflow] = '1';
      activitySet.activityTypes = [];

      var offset = 1;
      var names = _.pluck($scope.caseType.definition.activitySets, 'name');
      while (_.contains(names, workflow + '_' + offset)) offset++;
      activitySet.name = workflow + '_' + offset;
      activitySet.label = (offset == 1  ) ? $scope.workflows[workflow] : ($scope.workflows[workflow] + ' #' + offset);

      $scope.caseType.definition.activitySets.push(activitySet);
      _.defer(function() {
        $('.crmCaseType-acttab').tabs('refresh').tabs({active: -1});
      });
    };

    function formatActivityTypeOption(type) {
      return {id: type.name, text: type.label, icon: type.icon};
    }

    function addActivityToSet(activitySet, activityTypeName) {
      activitySet.activityTypes.push({
        name: activityTypeName,
        status: 'Scheduled',
        reference_activity: 'Open Case',
        reference_offset: '1',
        reference_select: 'newest'
      });
    }

    function createActivity(name, callback) {
      CRM.loadForm(CRM.url('civicrm/admin/options/activity_type', {action: 'add', reset: 1, label: name, component_id: 7}))
        .on('crmFormSuccess', function(e, data) {
          $scope.activityTypes[data.optionValue.name] = data.optionValue;
          $scope.activityTypeOptions.push(formatActivityTypeOption(data.optionValue));
          callback(data.optionValue);
          $scope.$digest();
        });
    }

    // Add a new activity entry to an activity-set
    $scope.addActivity = function(activitySet, activityType) {
      if ($scope.activityTypes[activityType]) {
        addActivityToSet(activitySet, activityType);
      } else {
        createActivity(activityType, function(newActivity) {
          addActivityToSet(activitySet, newActivity.name);
        });
      }
    };

    /// Add a new top-level activity-type entry
    $scope.addActivityType = function(activityType) {
      var names = _.pluck($scope.caseType.definition.activityTypes, 'name');
      if (!_.contains(names, activityType)) {
        // Add an activity type that exists
        if ($scope.activityTypes[activityType]) {
          $scope.caseType.definition.activityTypes.push({name: activityType});
        } else {
          createActivity(activityType, function(newActivity) {
            $scope.caseType.definition.activityTypes.push({name: newActivity.name});
          });
        }
      }
    };

    /// Add a new role
    $scope.addRole = function(roles, roleName) {
      var names = _.pluck($scope.caseType.definition.caseRoles, 'name');
      if (!_.contains(names, roleName)) {
        if (_.where($scope.relationshipTypeOptions, {id: roleName}).length) {
          roles.push({name: roleName});
        } else {
          CRM.loadForm(CRM.url('civicrm/admin/reltype', {action: 'add', reset: 1, label_a_b: roleName, label_b_a: roleName}))
            .on('crmFormSuccess', function(e, data) {
              roles.push({name: data.relationshipType[REL_TYPE_CNAME]});
              $scope.relationshipTypeOptions.push({id: data.relationshipType[REL_TYPE_CNAME], text: data.relationshipType.label_b_a});
              $scope.$digest();
            });
        }
      }
    };

    $scope.onManagerChange = function(managerRole) {
      angular.forEach($scope.caseType.definition.caseRoles, function(caseRole) {
        if (caseRole != managerRole) {
          caseRole.manager = '0';
        }
      });
    };

    $scope.removeItem = function(array, item) {
      var idx = _.indexOf(array, item);
      if (idx != -1) {
        array.splice(idx, 1);
      }
    };

    $scope.isForkable = function() {
      return !$scope.caseType.id || $scope.caseType.is_forkable;
    };

    $scope.newStatus = function() {
      CRM.loadForm(CRM.url('civicrm/admin/options/case_status', {action: 'add', reset: 1}))
        .on('crmFormSuccess', function(e, data) {
          $scope.caseStatuses[data.optionValue.name] = data.optionValue;
          $scope.selectedStatuses[data.optionValue.name] = true;
          $scope.$digest();
        });
    };

    $scope.isNewActivitySetAllowed = function(workflow) {
      switch (workflow) {
        case 'timeline':
          return true;
        case 'sequence':
          return 0 === _.where($scope.caseType.definition.activitySets, {sequence: '1'}).length;
        default:
          CRM.console('warn', 'Denied access to unrecognized workflow: (' + workflow + ')');
          return false;
      }
    };

    $scope.isActivityRemovable = function(activitySet, activity) {
      if (activitySet.name == 'standard_timeline' && activity.name == 'Open Case') {
        return false;
      } else {
        return true;
      }
    };

    $scope.isValidName = function(name) {
      return !name || name.match(/^[a-zA-Z0-9_]+$/);
    };

    $scope.getWorkflowName = function(activitySet) {
      var result = 'Unknown';
      _.each($scope.workflows, function(value, key) {
        if (activitySet[key]) result = value;
      });
      return result;
    };

    /**
     * Determine which HTML partial to use for a particular
     *
     * @return string URL of the HTML partial
     */
    $scope.activityTableTemplate = function(activitySet) {
      if (activitySet.timeline) {
        return '~/crmCaseType/timelineTable.html';
      } else if (activitySet.sequence) {
        return '~/crmCaseType/sequenceTable.html';
      } else {
        return '';
      }
    };

    $scope.dump = function() {
      console.log($scope.caseType);
    };

    $scope.save = function() {
      // Add selected statuses
      var selectedStatuses = [];
      _.each($scope.selectedStatuses, function(v, k) {
        if (v) selectedStatuses.push(k);
      });
      // Ignore if ALL or NONE selected
      $scope.caseType.definition.statuses = selectedStatuses.length == _.size($scope.selectedStatuses) ? [] : selectedStatuses;
      var result = crmApi('CaseType', 'create', $scope.caseType, true);
      result.then(function(data) {
        if (data.is_error === 0 || data.is_error == '0') {
          $scope.caseType.id = data.id;
          window.location.href = '#/caseType';
        }
      });
    };

    $scope.$watchCollection('caseType.definition.activitySets', function() {
      _.defer(function() {
        $('.crmCaseType-acttab').tabs('refresh');
      });
    });

    var updateCaseTypeName = function () {
      if (!$scope.caseType.id && $scope.locks.caseTypeName) {
        // Should we do some filtering? Lowercase? Strip whitespace?
        var t = $scope.caseType.title ? $scope.caseType.title : '';
        $scope.caseType.name = t.replace(/ /g, '_').replace(/[^a-zA-Z0-9_]/g, '').toLowerCase();
      }
    };
    $scope.$watch('locks.caseTypeName', updateCaseTypeName);
    $scope.$watch('caseType.title', updateCaseTypeName);

    if (!$scope.isForkable()) {
      CRM.alert(ts('The CiviCase XML file for this case-type prohibits editing the definition.'));
    }
  });

  crmCaseType.controller('CaseTypeListCtrl', function($scope, crmApi, caseTypes) {
    var ts = $scope.ts = CRM.ts(null);

    $scope.caseTypes = caseTypes.values;
    $scope.toggleCaseType = function (caseType) {
      caseType.is_active = (caseType.is_active == '1') ? '0' : '1';
      crmApi('CaseType', 'create', caseType, true)
        .catch(function (data) {
          caseType.is_active = (caseType.is_active == '1') ? '0' : '1'; // revert
          $scope.$digest();
        });
    };
    $scope.deleteCaseType = function (caseType) {
      crmApi('CaseType', 'delete', {id: caseType.id}, {
        error: function (data) {
          CRM.alert(data.error_message, ts('Error'), 'error');
        }
      })
        .then(function (data) {
          delete caseTypes.values[caseType.id];
          $scope.$digest();
        });
    };
    $scope.revertCaseType = function (caseType) {
      caseType.definition = 'null';
      caseType.is_forked = '0';
      crmApi('CaseType', 'create', caseType, true)
        .catch(function (data) {
          caseType.is_forked = '1'; // restore
          $scope.$digest();
        });
    };
  });

})(angular, CRM.$, CRM._);
