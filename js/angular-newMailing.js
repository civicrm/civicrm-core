(function(angular, $, _) {
//partials for the html pages
	var partialUrl = function(relPath) {
		return CRM.resourceUrls['civicrm'] + '/partials/crmMailingType/' + relPath;
	};

	var crmMailing = angular.module('crmMailing', ['ngRoute', 'ui.utils','ngSanitize']);
	var chck = []; //to fill the group variable $scope.incGroup
	var chck2= []; // to get id and text in the required format
	var mltokens = [];
	var global = 0;
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
							return {visibility: "Public Pages", url_tracking:"1", forward_replies:"0", auto_responder:"0", open_tracking:"1",
							};
						}
					}
				}
			}); //This route is used for creating new mails and editing the current mails
		}
	]);
//-----------------------------------------


//This controller is used in creating new mail and editing current mails
	crmMailing.controller('mailingCtrl', function($scope, crmApi, selectedMail, $location,$route, $sce, $window) {

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
		$scope.mailingGrp = CRM.crmMailing.mailGrp;
		$scope.user_id = CRM.crmMailing.contactid;
		$scope.currentMailing = selectedMail;
		$scope.currentMailing.created_id = $scope.user_id;
		mltokens = CRM.crmMailing.mailTokens;
		$scope.pre = false;
		//	$scope.sendtest = false;
		/*	$scope.set_sendtest = function(){
		 $scope.sendtest = true;
		 };*/
		$scope.noOfRecipients = 0;
		$scope.testMailing = {};
		$scope.testMailing.name = "";
		$scope.testMailing.group = "";
		window.ct = $scope.currentMailing;
		$scope.previewbody_html = "";
		$scope.previewbody_text = "";
		$scope.preview_subject = "";
		$scope.param = {};
		$scope.token = "";
		chck = [];
		chck2 = [];
		$scope.mailid = [];

		for (var a in $scope.mailingGrp) {
			if ($scope.mailingGrp[a].mailing_id==$scope.currentMailing.id) {
				var b = $scope.mailingGrp[a].entity_id + " " + $scope.mailingGrp[a].entity_table +" " + $scope.mailingGrp[a].group_type;
				var c = $scope.mailingGrp[a].id;
				chck.push(b);
				$scope.mailid.push(c);
			}
		}

		console.log(chck);
		console.log($scope.mailid);
		if ($scope.currentMailing.msg_template_id!=null) {
			$scope.tst=$scope.currentMailing.msg_template_id;
		}
		console.log($scope.tst);
		//Making the object for data

		for (var a in chck) {
			var b ={}
			b.id = chck[a];
			var splt = chck[a].split(" ");

			if(splt[1] == "civicrm_group"){
				for(var c in $scope.groupNamesList){
					if($scope.groupNamesList[c].id==splt[0]){
						b.text = $scope.groupNamesList[c].title;
					}
				}
			}
			if(splt[1] == "civicrm_mailing"){
				for(var c in $scope.mailList){
					if($scope.mailList[c].id==splt[0]){
						b.text = $scope.mailList[c].name;
					}
				}
			}
			chck2.push(b);
		}

		console.log(chck2);
		$scope.incGroup = chck2;


		$scope.mailingForm = function() {
			if ($scope.mailing_form.$valid) {
				// Submit as normal
			}
			else {
				$scope.mailing_form.submitted = true;
			}
		};

		$scope.back = function (){
			$window.location.href = "#/mailing" ;
			$route.reload();
		};

		$scope.tmp = function (tst){
			$scope.currentMailing.msg_template_id=tst;
			console.log($scope.currentMailing.msg_template_id+ "sasas");
			if($scope.currentMailing.msg_template_id == null){
				$scope.currentMailing.body_html="";
				$scope.currentMailing.subject="";
			}
			else{
				for(var a in $scope.tmpList){

					if($scope.tmpList[a].id==$scope.currentMailing.msg_template_id){
						$scope.currentMailing.body_html=$scope.tmpList[a].msg_html;
						console.log($scope.tmpList[a].msg_subject);
						$scope.currentMailing.subject=$scope.tmpList[a].msg_subject;
						console.log($scope.currentMailing.subject);
					}
				}
			}
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
		if($scope.currentMailing.scheduled_date == null || $scope.currentMailing.scheduled_date == "")
			$scope.now="1";
		else
			$scope.now="0";

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
			if($scope.acttab >=0){
				$scope.acttab =0;
			}
		};

		$scope.conclicked = function(){
			if($scope.acttab >=1){
				$scope.acttab =1;
			}
		};

		$scope.schedclicked = function(){
			if($scope.acttab >=2){
				$scope.acttab =2;
			}
		};

		//to split the value of selectedMail.scheduled_date into the date and time separately
		$scope.scheddate={};
		$scope.scheddate.date = "";
		$scope.scheddate.time = "";
		$scope.ans="";

		$scope.checkNow = function(){
			if($scope.now == 1 ){
				$scope.now = 0;
			}
			else{
				$scope.now = 1;
				$scope.currentMailing.scheduled_date = null;
				$scope.currentMailing.scheduled_id = null;
				console.log($scope.currentMailing.scheduled_date);
				$scope.scheddate.date = "";
				$scope.scheddate.time = "";
			}
		}

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
			console.log($scope.param.file_1.type);
		};

		$scope.upload_2 = function(){
			console.log($scope.param.file_2);
		};

		$scope.upload_3 = function(){
			console.log($scope.param.file_3);
		};

		$scope.preview_update = function(){
			var resulta =crmApi('Mailing','preview',{id:$scope.currentMailing.id});
			resulta.success(function(data) {
				if (data.is_error == 0) {
					console.log("came");
					console.log(data.values);
					$scope.previewbody_html=data.values.html;
					$scope.previewbody_text=data.values.text;
					$scope.preview_subject=data.values.subject;
					console.log($scope.preview_subject);
					$scope.pre = true;
					$scope.$digest();
					$scope.$apply();
				}
			});
		};

		$scope.isBody_text = function(){
			if($scope.currentMailing.body_text == null || $scope.currentMailing.body_text == "" )
				return false;
			else
				return true;
		};

		$scope.deliberatelyTrustDangerousSnippet = function() {
			return $sce.trustAsHtml($scope.previewbody_html);
		};

		$scope.deliberatelyTrustDangerousSnippet2 = function() {
			return $sce.trustAsHtml($scope.previewbody_text);
		};

		$scope.deliberatelyTrustDangerousSnippet3 = function() {
			return $sce.trustAsHtml($scope.preview_subject);
		};

		$scope.mailing_recipients= function() {
			console.log("the id is " + $scope.currentMailing.id);
			var resulta =crmApi('MailingRecipients', 'get', {mailing_id: $scope.currentMailing.id,  options: {limit:1000}});
			resulta.success(function(data) {
				if (data.is_error == 0) {
					console.log("Entered Mailing Recipients");
					console.log(data);
					$scope.noOfRecipients=data.count;
					console.log($scope.noOfRecipients);
					$scope.$digest();
					$scope.$apply();
				}
			});
		}

		$scope.mailingGroup = function() {
			var resulta =crmApi('MailingGroup', 'get', {mailing_id: $scope.currentMailing.id,  options: {limit:1000}})
			resulta.success(function(data) {
				console.log("I am awesome " );
				console.log(data.values);
				$scope.mailid = [];
				chck = [];
				angular.forEach(data.values, function(value,key){
					var b = value.entity_id + " " + value.entity_table +" " + value.group_type;
					var c = value.id;
					chck.push(b);
					$scope.mailid.push(c);
				});
				console.log(chck);
				console.log($scope.mailid);
				for(var a in chck)
				{	var b ={}
					b.id = chck[a];
					var splt = chck[a].split(" ");

					if(splt[1] == "civicrm_group"){
						for(var c in $scope.groupNamesList){
							if($scope.groupNamesList[c].id==splt[0]){
								b.text = $scope.groupNamesList[c].title;
							}
						}
					}
					if(splt[1] == "civicrm_mailing"){
						for(var c in $scope.mailList){
							if($scope.mailList[c].id==splt[0]){
								b.text = $scope.mailList[c].name;
							}
						}
					}
					chck2.push(b);
				}

				console.log(chck2);
				$scope.incGroup = chck2;
				$scope.mailing_recipients();
			});
		}

		$scope.save_next = function() {
			console.log($scope.testMailing.name + "THIS IS THE TEST MAILING");
			console.log($scope.testMailing.group + "THIS IS THE TEST MAILING GROUP");
			$scope.incGrp=[];
			$scope.excGrp=[];
			$scope.incMail=[];
			$scope.excMail=[];
			console.log(chck);
			$scope.answer="";
			for(req_id in chck){
				$scope.answer = chck[req_id].split(" ");

				if($scope.answer[1] == "civicrm_mailing" && $scope.answer[2]=="include"){
					$scope.incMail.push($scope.answer[0]);
				}
				else if($scope.answer[1] == "civicrm_mailing" && $scope.answer[2]=="exclude"){
					$scope.excMail.push($scope.answer[0]);
				}
				if($scope.answer[1] == "civicrm_group" && $scope.answer[2]=="include"){
					$scope.incGrp.push($scope.answer[0]);
				}
				else if($scope.answer[1] == "civicrm_group" && $scope.answer[2]=="exclude"){
					$scope.excGrp.push($scope.answer[0]);
				}
			}

			console.log($scope.incMail + " inc mail");
			console.log($scope.excMail + " exc mail");
			console.log($scope.incGrp + " inc group");
			console.log($scope.excGrp + " exc group");


			if ($scope.currentMailing.scheduled_date == $scope.currentMailing.approval_date
				&& $scope.currentMailing.scheduled_date != null) {
				console.log("Do Nothing");
			}
			else {
				$scope.currentMailing.scheduled_date= $scope.scheddate.date + " " + $scope.scheddate.time ;
				if ($scope.currentMailing.scheduled_date!=" ") {
					$scope.currentMailing.scheduled_id= $scope.user_id;
				}
				else {
					$scope.currentMailing.scheduled_date= null;
				}
			}
			console.log($scope.mailid + "coolio")
			if ($scope.mailid != null) {
				for (var a in $scope.mailid) {
					var result_2= crmApi('MailingGroup', 'delete', {
						id: $scope.mailid[a]
					});
				}
			}

			var result = crmApi('Mailing', 'create', {
					id: $scope.currentMailing.id,
					name: $scope.currentMailing.name,
					visibility:  $scope.currentMailing.visibility,
					created_id: $scope.currentMailing.created_id,
					subject: $scope.currentMailing.subject,
					msg_template_id: $scope.currentMailing.msg_template_id==null ? "" : $scope.currentMailing.msg_template_id,
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
					scheduled_date: $scope.currentMailing.scheduled_date==null ? "" : $scope.currentMailing.scheduled_date,
					scheduled_id: $scope.currentMailing.scheduled_id==null ? "" : $scope.currentMailing.scheduled_id,
					campaign_id:	$scope.currentMailing.campaign_id==null ? "" : $scope.currentMailing.campaign_id,
					header_id:	$scope.currentMailing.header_id,
					footer_id:	$scope.currentMailing.footer_id,
					groups: {include: $scope.incGrp,
						exclude: $scope.excGrp
					},
					mailings: {include: $scope.incMail,
						exclude: $scope.excMail
					},
					is_completed: $scope.currentMailing.is_completed,
					approver_id: $scope.currentMailing.approver_id,
					approval_status_id: $scope.currentMailing.approval_status_id,
					approval_date: $scope.currentMailing.approval_date,
				},
				true);
			//var result = crmApi('Mailing', 'create', $scope.currentMailing, true);
			result.success(function(data) {
				if (data.is_error == 0) {
					$scope.currentMailing.id = data.id;
					console.log("the id is " +	$scope.currentMailing.id );
					console.log("OK");
					console.log(data);
					$scope.mailingGroup();
				}
				console.log("OK2");
			});
		};

		$scope.save_next_page2 = function() {
			if ($scope.currentMailing.scheduled_date == $scope.currentMailing.approval_date
				&& $scope.currentMailing.scheduled_date != null) {
				console.log("Do Nothing");
			}
			else {
				$scope.currentMailing.scheduled_date= $scope.scheddate.date + " " + $scope.scheddate.time ;
				if ($scope.currentMailing.scheduled_date!=" ") {
					$scope.currentMailing.scheduled_id= $scope.user_id;
				}
				else {
					$scope.currentMailing.scheduled_date= null;
				}
			}

			var result = crmApi('Mailing', 'create', {
					id: $scope.currentMailing.id,
					name: $scope.currentMailing.name,
					visibility:  $scope.currentMailing.visibility,
					created_id: $scope.currentMailing.created_id,
					subject: $scope.currentMailing.subject,
					msg_template_id: $scope.currentMailing.msg_template_id==null ? "" : $scope.currentMailing.msg_template_id,
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
					scheduled_date: $scope.currentMailing.scheduled_date==null ? "" : $scope.currentMailing.scheduled_date,
					scheduled_id: $scope.currentMailing.scheduled_id==null ? "" : $scope.currentMailing.scheduled_id,
					campaign_id:	$scope.currentMailing.campaign_id==null ? "" : $scope.currentMailing.campaign_id,
					header_id:	$scope.currentMailing.header_id,
					footer_id:	$scope.currentMailing.footer_id,
					is_completed: $scope.currentMailing.is_completed,
					approver_id: $scope.currentMailing.approver_id,
					approval_status_id: $scope.currentMailing.approval_status_id,
					approval_date: $scope.currentMailing.approval_date,
				},
				true);
			//var result = crmApi('Mailing', 'create', $scope.currentMailing, true);
			result.success(function(data) {
				if (data.is_error == 0) {
					$scope.currentMailing.id = data.id;
					console.log("the id is " +	$scope.currentMailing.id );
					console.log("OK");
					console.log(data);
				}
				console.log("OK2");
			});
		};


		$scope.save = function() {
			$scope.save_next_page2();
			$scope.back();
		};

		$scope.save_page1 = function() {
			$scope.save_next_page1();
			$scope.back();
		};

		$scope.save_next_page1 = function() {
			if($scope.currentMailing.id == null){
				$scope.currentMailing.subject = $scope.currentMailing.name;
				$scope.save_next();
			}
			else {
				$scope.save_next();
			}

		};

		$scope.submitButton= function(){
			$scope.currentMailing.approval_status_id = "1";
			$scope.currentMailing.approver_id = $scope.user_id;
			var currentdate = new Date();
			var yyyy = currentdate.getFullYear();
			var mm = currentdate.getMonth() + 1;
			mm = mm<10 ? '0' + mm : mm;
			var dd = currentdate.getDate();
			dd = dd<10 ? '0' + dd : dd;
			var hh = currentdate.getHours();
			hh = hh<10 ? '0' + hh : hh;
			var min = currentdate.getMinutes();
			min = min<10 ? '0' + min : min;
			var sec = currentdate.getSeconds();
			sec = sec<10 ? '0' + sec : sec;
			$scope.currentMailing.approval_date = yyyy + "/"+ mm
				+ "/" + dd + " "
				+ hh + ":"
				+ min + ":" + sec;
			console.log($scope.now + "sched immediately");
			if($scope.now == 1){
				$scope.currentMailing.scheduled_date = $scope.currentMailing.approval_date;
			}
			console.log($scope.currentMailing.approval_date);
			$scope.save();
		};

		$scope.$watch('pre',function(){
			console.log("dsfdfsfds");
			if($scope.pre==true){
				$('#prevmail').dialog({
					title: 'Preview Mailing',
					width: 1080,
					height: 800,
					closed: false,
					cache: false,
					modal: true,
					close :function(){console.log("close");
						$scope.pre = false; $scope.$apply();}
				});
			}
		},true);

		$scope.sendTest = function(){
			console.log("Opened send Test");

			var resulta =crmApi('Mailing','send_test',{test_email:$scope.testMailing.name, test_group:$scope.testMailing.group,
				mailing_id:$scope.currentMailing.id});
			resulta.success(function(data) {
				console.log("worked");
			});
		};
	});


// Directive to go to the next tab    
	crmMailing.directive('nexttab', function() {
		return {
			restrict: 'A',
			link: function(scope, element, attrs) {
				$(element).parent().parent().tabs();
				var myarr = new Array(1,2);
				$(element).parent().parent().tabs({disabled:myarr});
				$(element).on("click",function() {
					scope.acttab=scope.acttab +1;
					var myArray1 = new Array( );
					for ( var i = 0; i < 3; i++ ) {
						if(scope.acttab!=i)
							myArray1.push(i);
					}
					$(element).parent().parent().tabs( "option", "disabled", myArray1 );
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
				var myarr = new Array(1,2);
				$(element).parent().parent().tabs({disabled:myarr});
				$(element).on("click",function() {
					scope.acttab=scope.acttab -1;
					var myArray1 = new Array( );
					for ( var i = 0; i < 3; i++ ) {
						if(scope.acttab!=i)
							myArray1.push(i);
					}
					$(element).parent().parent().tabs( "option", "disabled", myArray1 );
					$(element).parent().parent().tabs({active:scope.acttab});
					console.log("sid");
				});
			}
		};
	});

	// Select 2 Widget for selecting the included group 
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
					if(a[1]=="civicrm_group" && a[2]=="include")
						return "<img src='../../sites/all/modules/civicrm/i/include.jpeg' height=12 width=12/>" + "   " + "<img src='../../sites/all/modules/civicrm/i/group.png' height=12 width=12/>" + item.text;
					if(a[1]=="civicrm_group" && a[2]=="exclude")
						return "<img src='../../sites/all/modules/civicrm/i/Error.gif' height=12 width=12/>" + "   " + "<img src='../../sites/all/modules/civicrm/i/group.png' height=12 width=12/>" + item.text;
					if(a[1]=="civicrm_mailing" && a[2]=="include")
						return "<img src='../../sites/all/modules/civicrm/i/include.jpeg' height=12 width=12/>" + "   "  + "<img src='../../sites/all/modules/civicrm/i/EnvelopeIn.gif' height=12 width=12/>" + item.text;
					if(a[1]=="civicrm_mailing" && a[2]=="exclude")
						return "<img src='../../sites/all/modules/civicrm/i/Error.gif' height=12 width=12/>" + "   " + "<img src='../../sites/all/modules/civicrm/i/EnvelopeIn.gif' height=12 width=12/>" + item.text;
				}

				$(element).select2({
					width:"400px",
					placeholder: "Choose Recipients",
					formatResult: format,
					formatSelection: format,
					escapeMarkup: function(m) { return m; },
				}).select2("data", scope.incGroup);


				$(element).on("select2-opening", function()
				{ 	scope.incGroup=chck;
					console.log(scope.incGroup);
					scope.$apply();

				});

				$(element).on('select2-selecting', function(e) {
					chck.push(e.val);
					scope.incGroup=chck;
					scope.$apply();
					console.log(scope.incGroup);

				});

				$(element).on("select2-removed", function(e) {
					var index = chck.indexOf(e.val);
					chck.splice(index, 1);
					scope.incGroup=chck;
					console.log(scope.incGroup);
					scope.$apply();
				});

			}
		};
	});

	crmMailing.directive('groupselect',function(){
		return {
			restrict : 'AE',
			link: function(scope,element, attrs){
				$(element).select2({width:"200px", data: mltokens, placeholder:"Insert Token"});
				$(element).on('select2-selecting', function(e) {

					scope.$evalAsync('_resetSelection()');console.log(mltokens);
					var a = $(element).attr('id');
					if(a=="htgroup"){
						var msg = document.getElementById("body_html").value;
						var cursorlen = document.getElementById("body_html").selectionStart;
						console.log(cursorlen);
						var textlen   = msg.length;
						document.getElementById("body_html").value = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
						scope.currentMailing.body_html = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
						console.log(document.getElementById("body_html").value);
						console.log(scope.currentMailing.body_html);
						var cursorPos = (cursorlen + e.val.length);
						document.getElementById("body_html").selectionStart = cursorPos;
						document.getElementById("body_html").selectionEnd   = cursorPos;
						document.getElementById("body_html").focus();
					}
					else if(a=="subgroup"){
						var msg = document.getElementById("sub").value;
						var cursorlen = document.getElementById("sub").selectionStart;
						console.log(cursorlen);
						var textlen   = msg.length;
						document.getElementById("sub").value = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
						scope.currentMailing.subject = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
						console.log(document.getElementById("sub").value);
						console.log(scope.currentMailing.subject);
						var cursorPos = (cursorlen + e.val.length);
						document.getElementById("sub").selectionStart = cursorPos;
						document.getElementById("sub").selectionEnd   = cursorPos;
						document.getElementById("sub").focus();
					}
					else if(a=="textgroup"){
							var msg = document.getElementById("body_text").value;
							var cursorlen = document.getElementById("body_text").selectionStart;
							console.log(cursorlen);
							var textlen   = msg.length;
							document.getElementById("body_text").value = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
							scope.currentMailing.body_text = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
							console.log(document.getElementById("body_text").value);
							console.log(scope.currentMailing.body_text);
							var cursorPos = (cursorlen + e.val.length);
							document.getElementById("body_text").selectionStart = cursorPos;
							document.getElementById("body_text").selectionEnd   = cursorPos;
							document.getElementById("body_text").focus();
						}
					scope.$apply();
					e.preventDefault();
				})
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
						scope.scheddate.date=date.toString();
						scope.$apply();
						console.log(scope.scheddate.date);
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
					console.log(event);
					var file = files[0];
					scope.file = file ? file : undefined;
					scope.$apply();
				});
			}
		};
	});


	crmMailing.directive('checktimeentry',function(){
		return {
			restrict :'AE',
			link: function (scope, element, attrs) {
				$(element).timeEntry({show24Hours:true});
			}
		}
	});

	//This controller is used for creating the mailing list. Simply gets all the mailing data from civiAPI
	crmMailing.controller('mailingListCtrl', function($scope, crmApi, mailingList, $route) {
		if (global == 0) {
			global = global + 1;
			$route.reload();
		}
		$scope.mailingList = mailingList.values;
		$scope.deleteMail = function (mail) {
			crmApi('Mailing', 'delete', {id: mail.id}, {
				error: function (data) {
					CRM.alert(data.error_message, ts('Error'));
				}
			})
				.then(function (data) {
					if (!data.is_error) {
						console.log(mailingList.values);
						console.log(mailingList.values[mail.id]);
						delete mailingList.values[mail.id];
						$scope.$digest();
						$route.reload();
					}
				});

		};

		$scope.edit = function (){
			global = global - 1;
		};

	});

})(angular, CRM.$, CRM._);

/* example of params
 [attachFile_1] => Array ( [uri] => /var/www/siddhant/drupal-7.27/sites/default/files/civicrm/custom/blog_2_odt_2c622a7b5e32415a92e81ed97d6554c7.unknown [type] => application/vnd.oasis.opendocument.text [location] => /var/www/siddhant/drupal-7.27/sites/default/files/civicrm/custom/blog_2_odt_2c622a7b5e32415a92e81ed97d6554c7.unknown [description] => dasdas [upload_date] => 20140706105804 [tag] => Array ( ) [attachment_taglist] => Array ( ) )
 */
