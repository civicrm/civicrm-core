(function(angular, $, _) {

  var partialUrl = function(relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmCaseType/' + relPath;
  };

  var crmCaseType = angular.module('crmCaseType', ['ngRoute', 'ui.utils', 'crmUi', 'unsavedChanges']);

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
        templateUrl: partialUrl('list.html'),
        controller: 'CaseTypeListCtrl',
        resolve: {
          caseTypes: function($route, crmApi) {
            return crmApi('CaseType', 'get', {});
          }
        }
      });
      $routeProvider.when('/caseType/:id', {
        templateUrl: partialUrl('edit.html'),
        controller: 'CaseTypeCtrl',
        resolve: {
          apiCalls: function($route, crmApi) {
            var reqs = {};
            reqs.actStatuses = ['OptionValue', 'get', {
              option_group_id: 'activity_status'
            }];
            reqs.actTypes = ['OptionValue', 'get', {
              option_group_id: 'activity_type',
              options: {
                sort: 'name',
                limit: 0
              }
            }];
            reqs.relTypes = ['RelationshipType', 'get', {
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
      template: '<input class="add-activity" type="hidden" />',
      link: function(scope, element, attrs) {
        /// Format list of options for select2's "data"
        var getFormattedOptions = function() {
          return {
            results: _.map(scope[attrs.crmOptions], function(option){
              return {id: option, text: option};
            })
          };
        };

        var input = $('input', element);

        scope._resetSelection = function() {
          $(input).select2('close');
          $(input).select2('val', '');
          scope[attrs.crmVar] = '';
        };

        $(input).select2({
          data: getFormattedOptions,
          createSearchChoice: function(term) {
            return {id: term, text: term};
          }
        });
        $(input).on('select2-selecting', function(e) {
          scope[attrs.crmVar] = e.val;
          scope.$evalAsync(attrs.crmOnAdd);
          scope.$evalAsync('_resetSelection()');
          e.preventDefault();
        });

        scope.$watch(attrs.crmOptions, function(value) {
          $(input).select2('data', getFormattedOptions);
          $(input).select2('val', '');
        });
      }
    };
  });

  crmCaseType.controller('CaseTypeCtrl', function($scope, crmApi, apiCalls) {
    $scope.partialUrl = partialUrl;

    $scope.activityStatuses = _.values(apiCalls.actStatuses.values);
    $scope.activityTypes = apiCalls.actTypes.values;
    $scope.activityTypeNames = _.pluck(apiCalls.actTypes.values, 'name');
    $scope.relationshipTypeNames = _.pluck(apiCalls.relTypes.values, CRM.crmCaseType.REL_TYPE_CNAME); // CRM_Case_XMLProcessor::REL_TYPE_CNAME
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
    window.ct = $scope.caseType;

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

    /// Add a new activity entry to an activity-set
    $scope.addActivity = function(activitySet, activityType) {
      activitySet.activityTypes.push({
        name: activityType,
        status: 'Scheduled',
        reference_activity: 'Open Case',
        reference_offset: '1',
        reference_select: 'newest'
      });
      if (!_.contains($scope.activityTypeNames, activityType)) {
        $scope.activityTypeNames.push(activityType);
      }
    };

    /// Add a new top-level activity-type entry
    $scope.addActivityType = function(activityType) {
      var names = _.pluck($scope.caseType.definition.activityTypes, 'name');
      if (!_.contains(names, activityType)) {
        $scope.caseType.definition.activityTypes.push({
          name: activityType
        });

      }
      if (!_.contains($scope.activityTypeNames, activityType)) {
        $scope.activityTypeNames.push(activityType);
      }
    };

    /// Add a new role
    $scope.addRole = function(roles, roleName) {
      var names = _.pluck($scope.caseType.definition.caseRoles, 'name');
      if (!_.contains(names, roleName)) {
        roles.push({
          name: roleName
        });
      }
      if (!_.contains($scope.relationshipTypeNames, roleName)) {
        $scope.relationshipTypeNames.push(roleName);
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
      return !$scope.caseType.id || $scope.caseType.is_forkable
    };

    $scope.isNewActivitySetAllowed = function(workflow) {
      switch (workflow) {
        case 'timeline':
          return true;
        case 'sequence':
          return 0 == _.where($scope.caseType.definition.activitySets, {sequence: '1'}).length;
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
        return partialUrl('timelineTable.html');
      } else if (activitySet.sequence) {
        return partialUrl('sequenceTable.html');
      } else {
        return '';
      }
    };

    $scope.dump = function() {
      console.log($scope.caseType);
    };

    $scope.save = function() {
      var result = crmApi('CaseType', 'create', $scope.caseType, true);
      result.success(function(data) {
        if (data.is_error == 0) {
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
    $scope.caseTypes = caseTypes.values;
    $scope.toggleCaseType = function (caseType) {
      caseType.is_active = (caseType.is_active == '1') ? '0' : '1';
      crmApi('CaseType', 'create', caseType, true)
        .then(function (data) {
          if (data.is_error) {
            caseType.is_active = (caseType.is_active == '1') ? '0' : '1'; // revert
            $scope.$digest();
          }
        });
    };
    $scope.deleteCaseType = function (caseType) {
      crmApi('CaseType', 'delete', {id: caseType.id}, {
        error: function (data) {
          CRM.alert(data.error_message, ts('Error'));
        }
      })
        .then(function (data) {
          if (!data.is_error) {
            delete caseTypes.values[caseType.id];
            $scope.$digest();
          }
        });
    };
    $scope.revertCaseType = function (caseType) {
      caseType.definition = 'null';
      caseType.is_forked = '0';
      crmApi('CaseType', 'create', caseType, true)
        .then(function (data) {
          if (data.is_error) {
            caseType.is_forked = '1'; // restore
            $scope.$digest();
          }
        });
    };
  });

})(angular, CRM.$, CRM._);
