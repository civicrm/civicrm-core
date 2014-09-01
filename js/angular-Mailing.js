(function(angular, $, _) {
//partials for the html pages
  var partialUrl = function(relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailing/' + relPath;
  };

  var crmMailing = angular.module('crmMailing', ['ngRoute', 'ui.utils','ngSanitize']);
  var chck = []; //to fill the group variable $scope.incGroup
  var chck2= []; // to get id and text in the required format
  var mltokens = []; //we store list of the tokens in this
  var global = 0; //use this to reload mailingList page once

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
              //selected mail in case of a new mailing. some default values are set
              return {visibility: "Public Pages", url_tracking:"1",dedupe_email:"1", forward_replies:"0", auto_responder:"0", open_tracking:"1"
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
    $scope.partialUrl = partialUrl;
    $scope.campaignList =  CRM.crmMailing.campNames;
    $scope.mailList = CRM.crmMailing.civiMails;
    $scope.mailNameList = _.pluck(CRM.crmCaseType.civiMails, 'name');
    $scope.groupNamesList = CRM.crmMailing.groupNames;
    $scope.headerfooter = CRM.crmMailing.headerfooterList;
    $scope.fromAddress = CRM.crmMailing.fromAddress;
    $scope.tmpList = CRM.crmMailing.mesTemplate;
    $scope.mailingGrp = CRM.crmMailing.mailGrp;
    $scope.user_id = CRM.crmMailing.contactid;
    mltokens = CRM.crmMailing.mailTokens;
    //set currentMailing to selectedMail
    $scope.currentMailing = selectedMail;
    $scope.currentMailing.created_id = $scope.user_id;
    //when pre is true, preview mailing is opened
    $scope.pre = false;
    //counts number of recipients
    $scope.noOfRecipients = 0;
    //object testMailing for testMailing.name and testMailing.group
    $scope.testMailing = {};
    $scope.testMailing.name = "";
    $scope.testMailing.group = "";
    window.ct = $scope.currentMailing;
    //previewbody_html stores value of HTML in editor, similar functionality of text and subject based on name
    $scope.previewbody_html = "";
    $scope.previewbody_text = "";
    $scope.preview_subject = "";
    //object for tokens
    $scope.token = "";
    //chck and chck2 are used for mailing groups
    chck = [];
    chck2 = [];
    //from.name stores name of the sender, email stores the mail address of the sender, total stores the concatenation of the two
    //reply.email stores the reply to email address
    $scope.from = {};
    $scope.from.name = "";
    $scope.from.email = "";
    $scope.from.total = "";
    $scope.reply ={};
    $scope.reply.email = "";
    //replyaddress is the array used to save the set of fromemailAddress of the person
    $scope.replyaddress = [];
    for(var a in $scope.fromAddress){
      var b = {};
      var splt = $scope.fromAddress[a].label.split(" ");
      splt = splt[1].substring(1,(splt[1].length-1));
      b.email = splt;
      $scope.replyaddress.push(b);
    }
    //from.total retrieves the from.name + from.email value so it can be read by the select2 widget
    if ($scope.currentMailing.from_name != null) {
      $scope.from.name = $scope.currentMailing.from_name;
      $scope.from.email = $scope.currentMailing.from_email;
      $scope.from.total = '"'+ $scope.from.name +'"' + " <" + $scope.from.email + ">";
      $scope.reply.email = $scope.currentMailing.replyto_email;
    }

    $scope.mailid = [];
    //putting all the ids of mails corresponding to the current mailing in mailid.
    for (var a in $scope.mailingGrp) {
      if ($scope.mailingGrp[a].mailing_id==$scope.currentMailing.id) {
        var b = $scope.mailingGrp[a].entity_id + " " + $scope.mailingGrp[a].entity_table +" " + $scope.mailingGrp[a].group_type;
        var c = $scope.mailingGrp[a].id;
        chck.push(b);
        $scope.mailid.push(c);
      }
    }

    //used to put the Template id in tst so it can be used

    if ($scope.currentMailing.msg_template_id!=null) {
      $scope.tst=$scope.currentMailing.msg_template_id;
    }
    //Making the object for data in the mailing group related directive

    for (var a in chck) {
      var b ={};
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
    $scope.incGroup = chck2;
    //tabact is set to 0. stores value of active tab
    $scope.tabact = 0;
    //all tabs with tab index greater than maxtab will be disabled. if not a new mail, no tab will be disabled
    //otherwise all except first tab disabled
    if($scope.currentMailing.id != null)
      $scope.maxtab = 3;
    $scope.maxtab = 0;

    //increments active tab value and maxtab when next is clicked
    $scope.tabupdate = function(){
      $scope.tabact = $scope.tabact + 1;
      $scope.maxtab = Math.max($scope.maxtab,$scope.tabact);
    }
    //decrements active tab value
    $scope.prevtabupdate = function(){
      $scope.tabact = $scope.tabact - 1;
    }
    //set active tab to 0 when recipient is clicked
    $scope.recclicked = function(){
      $scope.tabact = 0;

    };
    //set active tab to 1 when content is clicked
    //also call appropriate save function if clicked from recipient tab
    $scope.conclicked = function(){
      if($scope.tabact == 0)
        $scope.save_next_page1();
      $scope.tabact = 1;
    };
    //set active tab to 2 when schedule and send is clicked
    //also call appropriate save functions if clicked from recipient or content tab
    $scope.schedclicked = function(){
      if($scope.tabact == 0)
        $scope.save_next_page1();
      else if($scope.tabact == 1)
        $scope.save_next_page2();
      $scope.tabact = 2;
    };
    //goes to the mailing list page
    $scope.back = function (){
      $window.location.href = "#/mailing" ;
      $route.reload();
    };
    //on changing the selected template, updates subject and body_html of currentMailing
    $scope.tmp = function (tst){
      $scope.currentMailing.msg_template_id=tst;
      if($scope.currentMailing.msg_template_id == null){
        $scope.currentMailing.body_html="";
        $scope.currentMailing.subject="";
      }
      else{
        for(var a in $scope.tmpList){

          if($scope.tmpList[a].id==$scope.currentMailing.msg_template_id){
            $scope.currentMailing.body_html=$scope.tmpList[a].msg_html;
            $scope.currentMailing.subject=$scope.tmpList[a].msg_subject;
          }
        }
      }
    };
    //initializing variables we will use for checkboxes, or for purpose of ng-show
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

    //joining scheddate.date and scheddate.time gives us scheduled date of current mailing
    $scope.scheddate={};
    $scope.scheddate.date = "";
    $scope.scheddate.time = "";
    //checkNow decides whether we see the date picker or not. Depends on send immediately being on or off
    $scope.checkNow = function(){
      if($scope.now == 1 ){
        $scope.now = 0;
      }
      else{
        $scope.now = 1;
        $scope.currentMailing.scheduled_date = null;
        $scope.currentMailing.scheduled_id = null;
        $scope.scheddate.date = "";
        $scope.scheddate.time = "";
      }
    };
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
    //we should only see groups with appropriate mailing visibility
    $scope.isGrp= function(grp){
      return grp.visibility == "Public Pages";
    };
    //only completed mails are shown in the groupings
    $scope.isCompMail= function(ml){
      return ml.is_completed == 1;
    };
    //This is used to open the update the values in preview mailing
    $scope.preview_update = function(){
      var resulta =crmApi('Mailing','preview',{id:$scope.currentMailing.id});
      resulta.success(function(data) {
        if (data.is_error == 0) {
          $scope.previewbody_html=data.values.html;
          $scope.previewbody_text=data.values.text;
          $scope.preview_subject=data.values.subject;
          $scope.pre = true;
          $scope.$digest();
          $scope.$apply();
        }
      });
    };
    //checks if body_text is empty or not
    $scope.isBody_text = function(){
      if($scope.currentMailing.body_text == null || $scope.currentMailing.body_text == "" )
        return false;
      else
        return true;
    };
    //parses html
    $scope.deliberatelyTrustDangerousSnippet = function() {
      return $sce.trustAsHtml($scope.previewbody_html);
    };

    $scope.deliberatelyTrustDangerousSnippet2 = function() {
      return $sce.trustAsHtml($scope.previewbody_text);
    };

    $scope.deliberatelyTrustDangerousSnippet3 = function() {
      return $sce.trustAsHtml($scope.preview_subject);
    };
    //gets number of mailing recipients
    $scope.mailing_recipients= function() {
      var resulta =crmApi('MailingRecipients', 'get', {mailing_id: $scope.currentMailing.id,  options: {limit:1000}});
      resulta.success(function(data) {
        if (data.is_error == 0) {
          $scope.noOfRecipients=data.count;
          $scope.$digest();
          $scope.$apply();
        }
      });
    }
    //gets mailing groups associated with current mailing id. formats it in the required way
    $scope.mailingGroup = function() {
      var resulta =crmApi('MailingGroup', 'get', {mailing_id: $scope.currentMailing.id,  options: {limit:1000}})
      resulta.success(function(data) {
        $scope.mailid = [];
        chck = [];
        angular.forEach(data.values, function(value,key){
          var b = value.entity_id + " " + value.entity_table +" " + value.group_type;
          var c = value.id;
          chck.push(b);
          $scope.mailid.push(c);
        });
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

        $scope.incGroup = chck2;
        $scope.mailing_recipients();
      });
    }
    //save repeat is a function that is repeated in all versions of save
    $scope.save_repeat = function(){
      if ($scope.from.total != "") {
        var splt = $scope.from.total.split(" ");
        var splta = splt[1].substring(1,(splt[1].length-1));
        var spltb = splt[0].substring(1,(splt[0].length-1));
        $scope.currentMailing.from_email = splta;
        $scope.currentMailing.from_name = spltb;
      }
    }
    //save_next is used in recipient page. stores mailing groups
    $scope.save_next = function() {
      $scope.save_repeat();
      //splits the groups based on include exclude mailing and groups
      $scope.incGrp=[];
      $scope.excGrp=[];
      $scope.incMail=[];
      $scope.excMail=[];
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
      //deletes existing mailing groups of that id
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
          dedupe_email: $scope.currentMailing.dedupe_email
        },
        true);
      //var result = crmApi('Mailing', 'create', $scope.currentMailing, true);
      result.success(function(data) {
        if (data.is_error == 0) {
          $scope.currentMailing.id = data.id;
          $scope.mailingGroup();
        }
      });
    };
    //in second page save neither groups or schedule date information.
    $scope.save_next_page2 = function() {
      $scope.save_repeat();
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
          campaign_id:	$scope.currentMailing.campaign_id==null ? "" : $scope.currentMailing.campaign_id,
          header_id:	$scope.currentMailing.header_id,
          footer_id:	$scope.currentMailing.footer_id,
          is_completed: $scope.currentMailing.is_completed,
          dedupe_email: $scope.currentMailing.dedupe_email
        },
        true);
      //var result = crmApi('Mailing', 'create', $scope.currentMailing, true);
      result.success(function(data) {
        if (data.is_error == 0) {
          $scope.currentMailing.id = data.id;
        }
      });
    };
    //save scheduling information and not groups
    $scope.save_next_page3 = function() {
      $scope.save_repeat();
      if ($scope.currentMailing.scheduled_date == $scope.currentMailing.approval_date
        && $scope.currentMailing.scheduled_date != null) {;
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
          dedupe_email: $scope.currentMailing.dedupe_email
        },
        true);
      //var result = crmApi('Mailing', 'create', $scope.currentMailing, true);
      result.success(function(data) {
        if (data.is_error == 0) {
          $scope.currentMailing.id = data.id;
        }
      });
    };

    //call save of page2 and go to listing page
    $scope.save = function() {
      $scope.save_next_page2();
      $scope.back();
    };
    //call save of page1 and go to listing page
    $scope.save_page1 = function() {
      $scope.save_next_page1();
      $scope.back();
    };
    //if we save on page one, subject isnt defined. so we equate it to mailing name for now so we can save
    $scope.save_next_page1 = function() {
      if($scope.currentMailing.id == null){
        $scope.currentMailing.subject = $scope.currentMailing.name;
        $scope.save_next();
      }
      else {
        $scope.save_next();
      }

    };
    //set approval date to current time, also schedule date based on send immediately or not. then call 3rd page save
    //go back to listing page
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
      if($scope.now == 1){
        $scope.currentMailing.scheduled_date = $scope.currentMailing.approval_date;
      }
      $scope.save_next_page3();
      $scope.back();
    };
    //we use this to open the preview modal based on value of pre
    $scope.$watch('pre',function(){
      if($scope.pre==true){
        $('#prevmail').dialog({
          title: 'Preview Mailing',
          width: 1080,
          height: 800,
          closed: false,
          cache: false,
          modal: true,
          close :function(){
            $scope.pre = false; $scope.$apply();
          }
        });
      }
    },true);
    //send test api called to send the test
    $scope.sendTest = function(){
      var resulta =crmApi('Mailing','send_test',{test_email:$scope.testMailing.name, test_group:$scope.testMailing.group,
        mailing_id:$scope.currentMailing.id});
    };
  });


// Directive to go to the next tab    
  crmMailing.directive('nexttab', function() {
    return {
      restrict: 'A',
      link: function(scope, element, attrs) {
        var tabselector = $(".crmMailingTabs");
        var myarr = new Array(1,2);
        if(scope.currentMailing.id==null){
          tabselector.tabs({disabled:myarr});
        }
        $(element).on("click",function() {
          scope.tabupdate();
          var myArray1 = new Array( );
          scope.$apply();
         for ( var i = scope.maxtab + 1; i < 3; i++ ) {
              myArray1.push(i);
          }

         tabselector.tabs( "option", "disabled", myArray1 );
         tabselector.tabs({active:scope.tabact});
          scope.$apply();
        });
      }
    };
  });

  // Directive to go to the previous tab
  crmMailing.directive('prevtab', function() {
    return {
      restrict: 'A',
      link: function(scope, element, attrs) {
        var tabselector = $(".crmMailingTabs");
        tabselector.tabs();
        $(element).on("click",function() {
          scope.prevtabupdate();
          scope.$apply();
          var myArray1 = new Array( );
          tabselector.tabs({active:scope.tabact});
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


        $(element).on("select2-opening", function(){
          scope.incGroup=chck;
          scope.$apply();

        });

        $(element).on('select2-selecting', function(e) {
          chck.push(e.val);
          scope.incGroup=chck;
          scope.$apply();

        });

        $(element).on("select2-removed", function(e) {
          var index = chck.indexOf(e.val);
          chck.splice(index, 1);
          scope.incGroup=chck;
          scope.$apply();
        });

      }
    };
  });
  //used for tokens select2 widget
  crmMailing.directive('groupselect',function(){
    return {
      restrict : 'AE',
      link: function(scope,element, attrs){
        $(element).select2({width:"200px", data: mltokens, placeholder:"Insert Token"});
        $(element).on('select2-selecting', function(e) {

          scope.$evalAsync('_resetSelection()');
          var a = $(element).attr('id');
          if(a=="htgroup"){
            if(scope.currentMailing.body_html =="" || scope.currentMailing.body_html == null)
              scope.currentMailing.body_html = e.val;
            else
              scope.currentMailing.body_html += e.val;
          }
          else if(a=="subgroup"){
            var msg = document.getElementById("sub").value;
            var cursorlen = document.getElementById("sub").selectionStart;
            var textlen   = msg.length;
            document.getElementById("sub").value = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
            scope.currentMailing.subject = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
            var cursorPos = (cursorlen + e.val.length);
            document.getElementById("sub").selectionStart = cursorPos;
            document.getElementById("sub").selectionEnd   = cursorPos;
            document.getElementById("sub").focus();
          }
          else if(a=="textgroup"){
              var msg = document.getElementById("body_text").value;
              var cursorlen = document.getElementById("body_text").selectionStart;
              var textlen   = msg.length;
              document.getElementById("body_text").value = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
              scope.currentMailing.body_text = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
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

  //ckeditor directive
  crmMailing.directive('ckedit', function() {
    return {
      require: '?ngModel',
      link: function(scope, elm, attr, ngModel) {
        var ck = CKEDITOR.replace(elm[0]);

        if (!ngModel) return;

        ck.on('pasteState', function() {
          scope.$apply(function() {
            ngModel.$setViewValue(ck.getData());
          });
        });

        ngModel.$render = function(value) {
          ck.setData(ngModel.$viewValue);
        };
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
          }
        });
      }
    };
  });
  //used for file upload
  crmMailing.directive('file', function(){
    return {
      scope: {
        file: '='
      },
      link: function(scope, el, attrs){
        el.bind('change', function(event){
          var files = event.target.files;
          var file = files[0];
          scope.file = file ? file : undefined;
          scope.$apply();
        });
      }
    };
  });
  //used to insert the time entry
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
    $scope.checkEmpty = function(){
    if($scope.mailingList == "")
    return true;
    else
    return false;
    }
    $scope.deleteMail = function (mail) {
      crmApi('Mailing', 'delete', {id: mail.id}, {
        error: function (data) {
          CRM.alert(data.error_message, ts('Error'));
        }
      })
        .then(function (data) {
          if (!data.is_error) {
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
