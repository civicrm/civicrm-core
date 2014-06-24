(function(angular, $, _) {

  var partialUrl = function(relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailingType/' + relPath;
  };

  var crmMailing = angular.module('crmMailing', ['ngRoute', 'ui.utils']);
  

 

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
              return {name: "New Mail", visibility: "Public Pages", url_tracking:"1", forward_replies:"0", created_id: "202", auto_responder:"0", open_tracking:"1",
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
	  
//Making some dummy api to see if my from email, reply to email works. To see if all options come in select 
//
  $scope.cool_api= [
    {'name': 'Nexus S',
     'from_mail': 'rajgo94@gmail.com',
     'reply_mail': 'rajgo94@gmail.com'  },
    {'name': 'Motorola XOOM™ with Wi-Fi',
     'from_mail': 'rajgo94@gmail2.com',
     'reply_mail': 'rajgo94@gmail2.com'},
    {'name': 'MOTOROLA XOOM™',
     'from_mail': 'rajgo94@gmail3.com',
     'reply_mail': 'rajgo94@gmail3.com'}
  ];

  $scope.partialUrl = partialUrl;
	$scope.campaignList =  CRM.crmMailing.campNames;
	$scope.mailNameList = _.pluck(CRM.crmCaseType.civiMails, 'name');
	$scope.groupNamesList = CRM.crmMailing.groupNames;
	$scope.headerfooter = CRM.crmMailing.headerfooterList;
	$scope.tmpList = CRM.crmMailing.mesTemplate;
	$scope.incGroup = [];
	$scope.excGroup = [];
	$scope.testGroup = [];
  $scope.currentMailing = selectedMail;
  window.ct = $scope.currentMailing;
  $scope.acttab=0;
	$scope.composeS="1";
	$scope.trackreplies="0";
	$scope.now="1";
	$scope.scheddate={};
  $scope.scheddate.date = ""; 
  $scope.scheddate.time = ""; 
  $scope.ans="";
  $scope.mailAutoResponder="";
	///changing upload on screen
/*		if(selectedMail.scheduled_date != ""){
			$scope.ans= selectedMail.scheduled_date.split(" ");
			$scope.scheddate.date=$scope.ans[0];
			$scope.scheddate.time=$scope.ans[1];
		}*/

	console.log(selectedMail); 
	$scope.upldChange= function(composeS){
		if(composeS=="1"){
			return true;
		}
		else 
			return false;
	}
	
	$scope.isHeader= function(hf){
		return hf.component_type == "Header";
	};

	$scope.isFooter= function(f){
		return f.component_type == "Footer";
	};
	
	$scope.isAuto= function(au){
		return au.component_type == "Reply";
	};
	
	$scope.isUserDriven= function(mstemp){
		return (parseInt(mstemp.id)>58);
	};	

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
    
    $scope.save = function() {
	    $scope.currentMailing.scheduled_date= $scope.scheddate.date + " " + $scope.scheddate.time ;
			if($scope.currentMailing.scheduled_date!=" "){
					$scope.currentMailing.scheduled_id= "202";
					$scope.currentMailing.scheduled_date= "";
			  }
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
  

crmMailing.directive('chsdate',function(){
        return {
            scope :{
                dat : '=send_date'
            },
          restrict: 'AE',
          link: function(scope,element,attrs){
                $(element).datepicker({
									  dateFormat: 'yy-mm-dd',
                    onSelect: function(date) {
                        $(".ui-datepicker a").removeAttr("href");
                        scope.dat =date;
                    }
                });
            }
        };
    });



crmMailing.controller('browse', function($scope){
    $scope.fileList = [];
    $('#fileupload').bind('fileuploadadd', function(e, data){
        // Add the files to the list
        numFiles = $scope.fileList.length
        for (var i=0; i < data.files.length; ++i) {
            var file = data.files[i];
        // .$apply to update angular when something else makes changes
        $scope.$apply(
            $scope.fileList.push({name: file.name})
            );
        }
        // Begin upload immediately
        data.submit();
    });
});

    
  crmMailing.directive('add',function(){
       return {
           restrict : 'AE',
           link: function(scope,element, attrs){
               $(document).ready(function(){
									$('#fileupload').fileupload({
									dataType: 'json'
									});
								});

			     
          }
       };
		}); 
    
    
 crmMailing.controller('mailingListCtrl', function($scope, crmApi, mailingList) {
    $scope.mailingList = mailingList.values;
    $scope.mailStatus = _.pluck(CRM.crmMailing.mailStatus, 'status');
  });

})(angular, CRM.$, CRM._);



