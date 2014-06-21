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
  

 
crmMailing.controller('browse', function(){
FileUploadCtrl.$inject = ['$scope']
function FileUploadCtrl(scope) {
    //============== DRAG & DROP =============
    // source for drag&drop: http://www.webappers.com/2011/09/28/drag-drop-file-upload-with-html5-javascript/
    var dropbox = document.getElementById("dropbox")
    scope.dropText = 'Drop files here...'

    // init event handlers
    function dragEnterLeave(evt) {
        evt.stopPropagation()
        evt.preventDefault()
        scope.$apply(function(){
            scope.dropText = 'Drop files here...'
            scope.dropClass = ''
        })
    }
    dropbox.addEventListener("dragenter", dragEnterLeave, false)
    dropbox.addEventListener("dragleave", dragEnterLeave, false)
    dropbox.addEventListener("dragover", function(evt) {
        evt.stopPropagation()
        evt.preventDefault()
        var clazz = 'not-available'
        var ok = evt.dataTransfer && evt.dataTransfer.types && evt.dataTransfer.types.indexOf('Files') >= 0
        scope.$apply(function(){
            scope.dropText = ok ? 'Drop files here...' : 'Only files are allowed!'
            scope.dropClass = ok ? 'over' : 'not-available'
        })
    }, false)
    dropbox.addEventListener("drop", function(evt) {
        console.log('drop evt:', JSON.parse(JSON.stringify(evt.dataTransfer)))
        evt.stopPropagation()
        evt.preventDefault()
        scope.$apply(function(){
            scope.dropText = 'Drop files here...'
            scope.dropClass = ''
        })
        var files = evt.dataTransfer.files
        if (files.length > 0) {
            scope.$apply(function(){
                scope.files = []
                for (var i = 0; i < files.length; i++) {
                    scope.files.push(files[i])
                }
            })
        }
    }, false)
    //============== DRAG & DROP =============

    scope.setFiles = function(element) {
    scope.$apply(function(scope) {
      console.log('files:', element.files);
      // Turn the FileList object into an Array
        scope.files = []
        for (var i = 0; i < element.files.length; i++) {
          scope.files.push(element.files[i])
        }
      scope.progressVisible = false
      });
    };

    scope.uploadFile = function() {
        var fd = new FormData()
        for (var i in scope.files) {
            fd.append("uploadedFile", scope.files[i])
        }
        var xhr = new XMLHttpRequest()
        xhr.upload.addEventListener("progress", uploadProgress, false)
        xhr.addEventListener("load", uploadComplete, false)
        xhr.addEventListener("error", uploadFailed, false)
        xhr.addEventListener("abort", uploadCanceled, false)
        xhr.open("POST", "/fileupload")
        scope.progressVisible = true
        xhr.send(fd)
    }

    function uploadProgress(evt) {
        scope.$apply(function(){
            if (evt.lengthComputable) {
                scope.progress = Math.round(evt.loaded * 100 / evt.total)
            } else {
                scope.progress = 'unable to compute'
            }
        })
    }

    function uploadComplete(evt) {
        /* This event is raised when the server send back a response */
        alert(evt.target.responseText)
    }

    function uploadFailed(evt) {
        alert("There was an error attempting to upload the file.")
    }

    function uploadCanceled(evt) {
        scope.$apply(function(){
            scope.progressVisible = false
        })
        alert("The upload has been canceled by the user or the browser dropped the connection.")
    }
}
});


 
 
 crmMailing.controller('mailingListCtrl', function($scope, crmApi, mailingList) {
    $scope.mailingList = mailingList.values;
    $scope.mailStatus = _.pluck(CRM.crmMailing.mailStatus, 'status');
  });

})(angular, CRM.$, CRM._);



