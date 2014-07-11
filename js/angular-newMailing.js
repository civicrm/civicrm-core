(function(angular, $, _) {
//partials for the html pages
  var partialUrl = function(relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailingType/' + relPath;
  };

  var crmMailing = angular.module('crmMailing', ['ngRoute', 'ui.utils']);
  var incGroup = [];

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
      });    //This route is used for generating the list of mails created.

    
 
      $routeProvider.when('/mailing/:id', {
        templateUrl: partialUrl('main.html'),
        controller: 'mailingCtrl',
        resolve: {
          selectedMail: function($route, crmApi) {
            if ( $route.current.params.id !== 'new') {
              return crmApi('Mailing', 'getsingle', {id: $route.current.params.id}); 
            }
            else {
            //created_id has been set to my id. Does not save without created_id. Needs to made generic based on the user
              return {visibility: "Public Pages", url_tracking:"1", forward_replies:"0", created_id: "202", auto_responder:"0", open_tracking:"1",
                 };
            }
          }
        }
      }); //This route is used for creating new mails and editing the current mails
    }
  ]);  
//-----------------------------------------
 

//This controller is used in creating new mail and editing current mails
	crmMailing.controller('mailingCtrl', function($scope, crmApi, selectedMail, $location) {
			
	//Making some dummy api to see if my from email, reply to email works. To see if all options come in select box
		$scope.cool_api= [
			{'name': 'rajgo94',
			 'from_mail': 'rajgo94@gmail.com',
			 'reply_mail': 'rajgo94@gmail.com'  },
			{'name': 'rajgo94_2',
			 'from_mail': 'rajgo94@gmail_2.com',
			 'reply_mail': 'rajgo94@gmail_2.com'},
			{'name': 'rajgo94_3',
			 'from_mail': 'rajgo94@gmail_3.com',
			 'reply_mail': 'rajgo94@gmail_3.com'}
		];
	//setting variables to the values we have got to the api
	  $scope.submitted = false;
		$scope.partialUrl = partialUrl;
		$scope.campaignList =  CRM.crmMailing.campNames;
		$scope.mailList = CRM.crmMailing.civiMails;
		$scope.mailNameList = _.pluck(CRM.crmCaseType.civiMails, 'name');
		$scope.groupNamesList = CRM.crmMailing.groupNames;
		$scope.headerfooter = CRM.crmMailing.headerfooterList;
		$scope.eMailing = CRM.crmMailing.emailAdd;
		$scope.tmpList = CRM.crmMailing.mesTemplate;
		$scope.currentMailing = selectedMail;
		$scope.testGroup = "";
		window.ct = $scope.currentMailing;
		$scope.param = {};
		$scope.mailingForm = function() {
			if ($scope.mailing_form.$valid) {
				// Submit as normal
			} else {
				$scope.mailing_form.submitted = true;
			}
		}
		
		$scope.back = function (){
			$location.path( "mailing" );
		};

	//initializing variables we will use for checkboxes, or for purpose of ng-show
		$scope.acttab=0;
		$scope.composeS="1";
		if($scope.currentMailing.forward_replies==0 && $scope.currentMailing.auto_responder==0){
				$scope.trackreplies="0";
		}
		else {
				$scope.trackreplies="1";
		}
		
		$scope.now="1";
		
		$scope.reply = function(){
			if($scope.trackreplies==0){
					$scope.trackreplies=1;
				}
			else{
					$scope.trackreplies=0;
					$scope.currentMailing.forward_replies=0;
					$scope.currentMailing.auto_responder=0;
				}
			}
			
		$scope.recclicked = function(){
			$scope.acttab=0;
		};

		$scope.conclicked = function(){
			$scope.acttab=1;
		};		
		
		$scope.schedclicked = function(){
			$scope.acttab=2;
		};		
		
	//to split the value of selectedMail.scheduled_date into the date and time separately	
		$scope.scheddate={};
		$scope.scheddate.date = ""; 
		$scope.scheddate.time = ""; 
		$scope.ans="";

	// To split the scheduled_date into date and time. The date format is not accepting 
		if(selectedMail.scheduled_date != null){
				$scope.ans= selectedMail.scheduled_date.split(" ");
				$scope.scheddate.date=$scope.ans[0];
				$scope.scheddate.time=$scope.ans[1];
				console.log("scheddate.date is " + $scope.scheddate.date);
				console.log("scheddate.time is " + $scope.scheddate.time);
			}

		console.log(selectedMail); 
		
	//changing the screen from compose on screen to upload content
		$scope.upldChange= function(composeS){
			if(composeS=="1"){
				return true;
			}
			else 
				return false;
		};
	//filter so we only get headers from mailing component 
		$scope.isHeader= function(hf){
			return hf.component_type == "Header";
		};
	//filter so we only get footers from mailing component 
		$scope.isFooter= function(f){
			return f.component_type == "Footer";
		};
	//filter so we only get auto-Responders from mailing component 	
		$scope.isAuto= function(au){
			return au.component_type == "Reply";
		};
	//filter so we only get userDriven message templates	
		$scope.isUserDriven= function(mstemp){
			return (parseInt(mstemp.id)>58);
		};	
	//used for ng-show when trackreplies is selected. Only then we show forward replies and auto-responders options
		$scope.trackr= function(trackreplies){
			if(trackreplies=="1"){
				return true;
			}
			else 
				return false;
		}
		
		$scope.isGrp= function(grp){
			return grp.visibility == "Public Pages";
		};
		
		$scope.isCompMail= function(ml){
			return ml.is_completed == 1;
		};
		
		$scope.upload = function(){
			console.log($scope.param.file);
			};
		
		$scope.upload_2 = function(){
			console.log($scope.param.file_2);
			};
		
		$scope.upload_3 = function(){
			console.log($scope.param.file_3);
		};
				
			
		$scope.save = function() {
				$scope.incGrp=[];
				$scope.excGrp=[];
				$scope.incMail=[];
				$scope.excMail=[];
				console.log($scope.testGroup + " test group");
				console.log(incGroup);
				$scope.answer="";
				for(req_id in incGroup){
					$scope.answer = incGroup[req_id].split(" ");
			
					if($scope.answer[1] == "mail" && $scope.answer[2]=="include"){
						$scope.incMail.push($scope.answer[0]);
						}
					else if($scope.answer[1] == "mail" && $scope.answer[2]=="exclude"){
						$scope.excMail.push($scope.answer[0]);
						}
					if($scope.answer[1] == "group" && $scope.answer[2]=="include"){
						$scope.incGrp.push($scope.answer[0]);
						}
					else if($scope.answer[1] == "group" && $scope.answer[2]=="exclude"){
						$scope.excGrp.push($scope.answer[0]);
						}
				}
				
				console.log($scope.incMail + " inc mail");
				console.log($scope.excMail + " exc mail");
				console.log($scope.incGrp + " inc group");
				console.log($scope.excGrp + " exc group");
				
				for(a in $scope.incMail){
					for(b in $scope.excMail){
						if($scope.excMail[b]==$scope.incMail[a]){
							console.log("should not happen same mail with id " + $scope.incMail[a] + " excluded and included");
						}
					}
				}
				
				for(a in $scope.incGrp){
					for(b in $scope.excGrp){
						if($scope.excGrp[b]==$scope.incGrp[a]){
							console.log("should not happen same group with id " + $scope.incGrp[a] + " excluded and included");
						}
					}
				}
				
				$scope.currentMailing.scheduled_date= $scope.scheddate.date + " " + $scope.scheddate.time ;
				if($scope.currentMailing.scheduled_date!=" "){
						$scope.currentMailing.scheduled_id= "202";
					}
				else {
						$scope.currentMailing.scheduled_date= null;
				}
				var result = crmApi('Mailing', 'create', {
					id: $scope.currentMailing.id,
					name: $scope.currentMailing.name, 
					visibility:  $scope.currentMailing.visibility, 
					created_id: $scope.currentMailing.created_id, 
					subject: $scope.currentMailing.subject, 
					msg_template_id: $scope.currentMailing.msg_template_id,
					open_tracking: $scope.currentMailing.open_tracking,
					url_tracking: $scope.currentMailing.url_tracking,
					forward_replies: $scope.currentMailing.forward_replies,
					auto_responder: $scope.currentMailing.auto_responder,
					from_name: $scope.currentMailing.from_name,
					from_email: $scope.currentMailing.from_email,
					replyto_email: $scope.currentMailing.replyto_email,
					unsubscribe_id: $scope.currentMailing.unsubscribe_id,
					resubscribe_id: $scope.currentMailing.resubscribe_id,
					body_html: $scope.currentMailing.body_html,
					body_text: $scope.currentMailing.body_text,
					scheduled_date: $scope.currentMailing.scheduled_date,
					scheduled_id: $scope.currentMailing.scheduled_id,
					campaign_id:	$scope.currentMailing.campaign_id,
					header_id:	$scope.currentMailing.header_id,
					footer_id:	$scope.currentMailing.footer_id,
					groups: {include: $scope.incGrp,
									 exclude: $scope.excGrp
									 },
					mailings: {include: $scope.incMail,
										 exclude: $scope.excMail
										},
					is_completed: $scope.currentMailing.is_completed,
					}, 
				true);			
				//var result = crmApi('Mailing', 'create', $scope.currentMailing, true);
				result.success(function(data) {
					if (data.is_error == 0) {
						$scope.currentMailing.id = data.id;
						console.log("OK");
					}
					console.log("OK2");
				});
		 };
	});
	 

// Directive to go to the next tab    
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

	// Directive to go to the previous tab    
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

	// Select 2 Widget for selecting the group 
	crmMailing.directive('chsgroup',function(){
		return {
			restrict : 'AE',
			link: function(scope,element, attrs){
			   function format(item) {
         if(!item.id) {
            // return `text` for optgroup
            return item.text;
          }
          // return item template
          var a = item.id.split(" ");
          console.log(a);
          if(a[1]=="group" && a[2]=="include")
						return "<img src='../../sites/all/modules/civicrm/i/include.jpeg' height=12 width=12/>" + "   " + "<img src='../../sites/all/modules/civicrm/i/group.png' height=12 width=12/>" + item.text;
				  if(a[1]=="group" && a[2]=="exclude")
						return "<img src='../../sites/all/modules/civicrm/i/Error.gif' height=12 width=12/>" + "   " + "<img src='../../sites/all/modules/civicrm/i/group.png' height=12 width=12/>" + item.text;
					if(a[1]=="mail" && a[2]=="include")
						return "<img src='../../sites/all/modules/civicrm/i/include.jpeg' height=12 width=12/>" + "   "  + "<img src='../../sites/all/modules/civicrm/i/EnvelopeIn.gif' height=12 width=12/>" + item.text;
					if(a[1]=="mail" && a[2]=="exclude")
						return "<img src='../../sites/all/modules/civicrm/i/Error.gif' height=12 width=12/>" + "   " + "<img src='../../sites/all/modules/civicrm/i/EnvelopeIn.gif' height=12 width=12/>" + item.text;
        }

			$(element).select2({
				width:"400px",
				placeholder: "Choose Recipients",
				formatResult: format,
				formatSelection: format,
				escapeMarkup: function(m) { return m; }
				});
			$(element).on('select2-selecting', function(e) {
					incGroup.push(e.val);
          console.log(incGroup);
        });
			}
		};
	}); 
		
	// Used for the select date option. This is used for giving scheduled_date its date value
	crmMailing.directive('chsdate',function(){
		return {
			restrict: 'AE',
			link: function(scope,element,attrs){
					$(element).datepicker({
						dateFormat: "yy-mm-dd",
						onSelect: function(date) {
							$(".ui-datepicker a").removeAttr("href");
							var ngModelName = this.attributes['ng-model'].value;
              scope[ngModelName] = date;
              scope.$apply();
						}
				});
			}
		};
	});

	crmMailing.directive('file', function(){
			return {
					scope: {
							file: '='
					},
					link: function(scope, el, attrs){
							el.bind('change', function(event){
									var files = event.target.files;
									var file = files[0];
									scope.file = file ? file.name : undefined;
									scope.$apply();
							});
					}
			};
	});

			
	 //This controller is used for creating the mailing list. Simply gets all the mailing data from civiAPI   
	crmMailing.controller('mailingListCtrl', function($scope, crmApi, mailingList) {
		$scope.mailingList = mailingList.values;
		$scope.mailStatus = _.pluck(CRM.crmMailing.mailStatus, 'status');
	});

})(angular, CRM.$, CRM._);

/* example of params
 [attachFile_1] => Array ( [uri] => /var/www/siddhant/drupal-7.27/sites/default/files/civicrm/custom/blog_2_odt_2c622a7b5e32415a92e81ed97d6554c7.unknown [type] => application/vnd.oasis.opendocument.text [location] => /var/www/siddhant/drupal-7.27/sites/default/files/civicrm/custom/blog_2_odt_2c622a7b5e32415a92e81ed97d6554c7.unknown [description] => dasdas [upload_date] => 20140706105804 [tag] => Array ( ) [attachment_taglist] => Array ( ) )
*/
