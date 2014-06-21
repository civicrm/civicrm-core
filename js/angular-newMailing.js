(function(angular, $, _) {

  var partialUrl = function(relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailingType/' + relPath;
  };

  var crmMailing = angular.module('crmMailing', ['ngRoute', 'ui.utils']);

 /* var sid = {
	  collapsible: true,
	  active: false
  };*/
//-------------------------------------------------------------------------------------------------------
 crmMailing.config(['$routeProvider',
    function($routeProvider) {
      $routeProvider.when('/mailing', {
        templateUrl: partialUrl('mailingList.html'),
        controller: 'mailingListCtrl',
        resolve: {
          mailingList: function($route, crmApi) {
            return crmApi('Mailing', 'get', {});
          }

        }
      });    

    
 
      $routeProvider.when('/mailing/:id', {
        templateUrl: partialUrl('main.html'),
        controller: 'mailingCtrl',
        resolve: {
          selectedMail: function($route, crmApi) {
            if ( $route.current.params.id !== 'new') {
              return crmApi('Mailing', 'getsingle', {id: $route.current.params.id}); 
            }
            else {
              return {name: "New Mail", visibility: "Public Pages",  url_tracking:"1", forward_replies:"0", auto_responder:"0", open_tracking:"1",
                 };
            }
          }
        }
      }); 
    }
  ]);  
//-----------------------------------------
  // Add a new record by name.
  // Ex: <crmAddName crm-options="['Alpha','Beta','Gamma']" crm-var="newItem" crm-on-add="callMyCreateFunction(newItem)" />
 


 
 
  crmMailing.controller('mailingCtrl', function($scope, crmApi, selectedMail) {
    $scope.partialUrl = partialUrl;
	$scope.campaignList =  CRM.crmMailing.campNames;
	$scope.mailNameList = _.pluck(CRM.crmCaseType.civiMails, 'name');
	$scope.groupNamesList = CRM.crmMailing.groupNames;
	$scope.incGroup = [];
	$scope.excGroup = [];
    $scope.currentMailing = selectedMail;
    /*$scope.currentMailing.name = $scope.currentMailing.name || [];
    $scope.currentMailing.visibility = $scope.currentMailing.visibility || [];
    $scope.currentMailing.url_tracking = $scope.currentMailing.url_tracking || [];
    $scope.currentMailing.forward_replies = $scope.currentMailing.forward_replies || [];
    $scope.currentMailing.auto_responder = $scope.currentMailing.auto_responder || [];
    $scope.currentMailing.open_tracking = $scope.currentMailing.open_tracking || [];*/
    window.ct = $scope.currentMailing;
    $scope.acttab=0;
	$scope.composeS="1";
	$scope.trackreplies="0";
	///changing upload on screen

	$scope.upldChange= function(composeS){
		if(composeS=="1"){
			return true;
		}
		else 
			return false;
	}
	
	$scope.trackr= function(trackreplies){
		if(trackreplies=="1"){
			return true;
		}
		else 
			return false;
	}
	
	/// Add a new group to mailing
 /*   $scope.addGroup = function(grp, groupName) {
      var names = _.pluck(CRM.crmMailing.groupNames, 'name');
      if (!_.contains(names, groupName)) {
        grp.push({
          name: groupName
        });
      }
    }; */
    
    
    
   /* $scope.addActivitySet = function(workflow) {
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
	  $scope.save = function() {
      var result = crmApi('Mailing', 'create', $scope.currentMailing, true);
      result.success(function(data) {
        if (data.is_error == 0) {
          $scope.currentMailing.id = data.id;
          console.log("OK");
        }
        console.log("OK2");
      });
    };
  });
 
 
/* crmMailing.directive('crmAddMail', function() {
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
  }); */


    /*crmMailing.directive('nexttab', function() {
        return {
            // Restrict it to be an attribute in this case
            restrict: 'A',
            // responsible for registering DOM listeners as well as updating the DOM
            link: function(scope, element, attrs) {

				$(element).parent().parent().tabs();
				$(element).on("click",function() {
                    var selected =  $(element).parent().parent().tabs("option","selected");
                    $(element).parent().parent().tabs("select", selected + 1);
                    
                    
                });
            }
        };
    });*/
    
        crmMailing.directive('nexttab', function() {
        return {

            restrict: 'A',
			link: function(scope, element, attrs) {

                $(element).parent().parent().tabs();

                $(element).on("click",function() {
                    scope.acttab=scope.acttab +1;
                    $(element).parent().parent().tabs({active:scope.acttab});
                    console.log("sid");
                });
            }
        };
    });
     
        crmMailing.directive('prevtab', function() {
        return {

            restrict: 'A',
			link: function(scope, element, attrs) {

                $(element).parent().parent().tabs();

                $(element).on("click",function() {
                    scope.acttab=scope.acttab -1;
                    $(element).parent().parent().tabs({active:scope.acttab});
                    console.log("sid");
                });
            }
        };
    }); 

    
    crmMailing.directive('chsgroup',function(){
       return {
           restrict : 'AE',
           link: function(scope,element, attrs){
               $(element).select2(
                 {width:"400px",
			      placeholder: "Include Group",
			     });
           }
       };

    }); 
  

 

 
 
 crmMailing.controller('mailingListCtrl', function($scope, crmApi, mailingList) {
    $scope.mailingList = mailingList.values;
    $scope.mailStatus = _.pluck(CRM.crmMailing.mailStatus, 'status');
  });

})(angular, CRM.$, CRM._);


