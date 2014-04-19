(function(angular, $, _) {

  var partialsUrl = CRM.resourceUrls['civicrm'] + '/partials/crmCaseType';
  var crmCaseType = angular.module('crmCaseType', ['ngRoute']);

  crmCaseType.config(['$routeProvider',
    function($routeProvider) {
      $routeProvider.when('/caseType/:id', {
        templateUrl: partialsUrl + '/edit.html',
        controller: 'CaseTypeCtrl'
      });
    }
  ]);

  crmCaseType.controller('CaseTypeCtrl', function($scope) {
    $scope.partialsUrl = partialsUrl;
    $scope.caseType = {
      id: 123,
      label: 'Adult Day Care Referral',
      description: 'Superkalafragalisticexpialitotious',
      is_active: '1', // Angular won't bind checkbox correctly with numeric 1
      definition: {  // This is the serialized field
        name: 'Adult Day Care Referral',
        activityTypes: [
          {name: 'Open Case', max_instances: 1 },
          {name: 'Medical evaluation'}
        ],
        activitySets: [
          {
            name: 'standard_timeline',
            label: 'Standard Timeline',
            timeline: '1', // Angular won't bind checkbox correctly with numeric 1
            activityTypes: [
              {name: 'Open Case', status: 'Completed' },
              {name: 'Medical evaluation', reference_activity: 'Open Case', reference_offset: 3, reference_select: 'newest'}
            ]
          },
          {
            name: 'my_sequence',
            label: 'Sequence',
            pipeline: '1', // Angular won't bind checkbox correctly with numeric 1
            activityTypes: [
              {name: 'Medical evaluation'},
              {name: 'Meeting'},
              {name: 'Phone Call'}
            ]
          }

        ],
        caseRoles: [
          { name: 'Senior Services Coordinator', creator: '1', manager: '1' },
          { name: 'Health Services Coordinator' },
          { name: 'Benefits Specialist' }
        ]
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

    $scope.getWorkflowName = function(activitySet) {
      if (activitySet.timeline) return "Timeline";
      if (activitySet.pipeline) return "Sequence";
      return "Unknown";
    };

    /**
     * Determine which HTML partial to use for a particular
     *
     * @return string URL of the HTML partial
     */
    $scope.activityTableTemplate = function(activitySet) {
      if (activitySet.timeline) {
        return partialsUrl + '/timelineTable.html';
      } else if (activitySet.pipeline) {
        return partialsUrl + '/pipelineTable.html';
      } else {
        return '';
      }
    };

    $scope.dump = function() {
      console.log($scope.caseType);
    }
  });

})(angular, CRM.$, CRM._);