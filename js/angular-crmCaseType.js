(function(angular, $, _) {

  var partialUrl = function(relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmCaseType/' + relPath;
  };

  var crmCaseType = angular.module('crmCaseType', ['ngRoute', 'ui.utils']);

  var newCaseTypeDefinitionTemplate = {
    activityTypes: [
      {name: 'Open Case', max_instances: 1 }
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
          selectedCaseType: function($route, crmApi) {
            if ( $route.current.params.id !== 'new') {
              return crmApi('CaseType', 'getsingle', {id: $route.current.params.id});
            }
            else {
              return { title: "New case type", name: "New case type", is_active: "1", weight: "1",
                definition: _.extend({}, newCaseTypeDefinitionTemplate) };
            }
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
      scope: {
        crmOptions: '=',
        crmVar: '=',
        crmOnAdd: '&'
      },
      templateUrl: partialUrl('addName.html')
    };
  });

  crmCaseType.controller('CaseTypeCtrl', function($scope, crmApi, selectedCaseType) {
    $scope.partialUrl = partialUrl;

    $scope.activityStatuses = CRM.crmCaseType.actStatuses;
    $scope.activityTypes = CRM.crmCaseType.actTypes;
    $scope.activityTypeNames = _.pluck(CRM.crmCaseType.actTypes, 'name');
    $scope.relationshipTypeNames = _.pluck(CRM.crmCaseType.relTypes, 'label_b_a'); // label_b_a is CRM_Case_XMLProcessor::REL_TYPE_CNAME

    $scope.workflows = {
      'timeline': 'Timeline',
      'sequence': 'Sequence'
    };

    $scope.caseType = selectedCaseType;
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
        name: activityType
      });
    };

    /// Add a new top-level activity-type entry
    $scope.addActivityType = function(activityType) {
      var names = _.pluck($scope.caseType.definition.activityTypes, 'name');
      if (!_.contains(names, activityType)) {
        $scope.caseType.definition.activityTypes.push({
          name: activityType
        });
      }
    };

    /// Add a new role
    $scope.addRole = function(roles, roleName) {
      roles.push({
        name: roleName
      });
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

    $scope.isNewActivitySetAllowed = function(workflow) {
      switch (workflow) {
        case 'timeline':
          return true;
        case 'sequence':
          return 0 == _.where($scope.caseType.definition.activitySets, {sequence: '1'}).length;
        default:
          if (console && console.log) console.log('Denied access to unrecognized workflow: (' + workflow + ')');
          return false;
      }
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
        }
      });
    };

    $scope.$watchCollection('caseType.definition.activitySets', function() {
      _.defer(function() {
        $('.crmCaseType-acttab').tabs('refresh');
      });
    });
  });

  crmCaseType.controller('CaseTypeListCtrl', function($scope, crmApi, caseTypes) {
    $scope.caseTypes = caseTypes.values;
  });

})(angular, CRM.$, CRM._);