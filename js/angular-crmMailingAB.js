/**
 * Created by aditya on 6/12/14.
 */


(function (angular, $, _) {

  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/abtesting/' + relPath;
  };

  var crmMailingAB = angular.module('crmMailingAB', ['ngRoute', 'ui.utils']);
  var mltokens = [];
  crmMailingAB.run(function ($rootScope, $templateCache) {
    $rootScope.$on('$viewContentLoaded', function () {
      $templateCache.removeAll();
    });
  });

  crmMailingAB.config([
    '$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/mailing/abtesting', {
        templateUrl: partialUrl('list.html'),
        controller: 'ListingCtrl',
        resolve: {
          mailingList: function ($route, crmApi) {
            return crmApi('Mailing', 'get', {});
          }
        }
      });
      $routeProvider.when('/mailing/abtesting/:id', {
        templateUrl: partialUrl('main.html'),
        controller: 'TabsDemoCtrl',
        resolve: {
          selectedABTest: function($route, crmApi) {
            if ( $route.current.params.id !== 'new') {

              return crmApi('MailingAB', 'getsingle', {id: $route.current.params.id});
            }
            else {
              //created_id has been set to my id. Does not save without created_id. Needs to made generic based on the user
              return { just_created:"1"
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
  crmMailingAB.controller('ListingCtrl', function ($scope, crmApi) {
    $scope.abmailList = CRM.crmMailing.mailingabNames;
    console.log($scope.abmailList);


  })
  crmMailingAB.controller('TabsDemoCtrl', function ($scope, crmApi,selectedABTest) {
    $scope.abId="";
    $scope.whatnext=2;
    $scope.currentABTest=selectedABTest
    $scope.groups = CRM.crmMailing.groupNames;
    $scope.mailList = CRM.crmMailing.civiMails;
    $scope.eMailing = CRM.crmMailing.emailAdd;
    $scope.tmpList = CRM.crmMailing.mesTemplate;
    $scope.headerfooter = CRM.crmMailing.headerfooterList;
    if($scope.currentABTest.declare_winning_time != null){
      $scope.ans= $scope.currentABTest.declare_winning_time.split(" ");
      $scope.currentABTest.date=$scope.ans[0];
      $scope.currentABTest.time=$scope.ans[1];

    }

      if($scope.currentABTest.just_created != 1){
        console.log("Prithvi");
        console.log($scope.currentABTest);
        console.log($scope.currentABTest.mailing_id_a);

        $scope.abId = $scope.currentABTest.id;
        var abmailA = crmApi('Mailing','getsingle',{id:$scope.currentABTest.mailing_id_a});
        var abmailB= crmApi('Mailing','getsingle',{id:$scope.currentABTest.mailing_id_b});
        abmailA.success(function (data) {
          if (data.is_error == 0) {
            $scope.mailA = data;

          };
        });
        abmailB.success(function(data) {
          if (data.is_error == 0) {
            $scope.mailB = data;

          };
        });
      }
      else{
        console.log("Prithvila");
        console.log($scope.currentABTest);
        $scope.mailA = {};
        $scope.mailB = {};
      }

    $scope.templates =
      [
        { name: 'Subject Lines', url: partialUrl('subject_lines.html'),val: 1},
        { name: 'From Name', url: partialUrl('from_name.html'),val:2},
        {name: 'Two different Emails', url: partialUrl('two_emails.html'),val:3}
      ];
    if(typeof $scope.template == 'undefined'){
    console.log("adi");
      $scope.template = $scope.templates[0];
  }
    else{
      console.log("adit "+$scope.currentABTest.testing_criteria_id);

      $scope.template=$scope.templates[$scope.currentABTest.testing_criteria_id-1];
      console.log($scope.template.val);
    }

    mltokens = CRM.crmMailing.mailTokens;

    $scope.tab_val = 0;
    $scope.max_tab = 0;
    $scope.campaign_clicked = function () {
      if ($scope.max_tab >= 0) {
        $scope.tab_val = 0;
      }
    };

    $scope.winner_criteria="";
    $scope.compose_clicked = function () {
      if ($scope.max_tab >= 1) {
        $scope.tab_val = 1;
      }
    };
    $scope.rec_clicked = function () {
      if ($scope.max_tab >= 2) {
        $scope.tab_val = 2;
      }
    };
    $scope.preview_clicked = function () {
      if ($scope.max_tab >= 3) {
        $scope.tab_val = 3;
      }
    };

    $scope.slide_value = 0;

    $scope.setifyes = function (val) {
      if (val == 1) {
        $scope.ifyes = true;
      }
      else {
        $scope.ifyes = false;
      }
    };

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

    $scope.isAuto= function(au){
      return au.component_type == "Reply";
    };

    $scope.trackr= function(trackreplies){
      if(trackreplies=="1"){
        return true;
      }
      else
        return false;
    }



    $scope.isHeader= function(hf){
      return hf.component_type == "Header";
    };
    //filter so we only get footers from mailing component
    $scope.isFooter= function(f){
      return f.component_type == "Footer";
    };

    $scope.send_date = "01/01/2000";

    $scope.dt = "";


    $scope.savea = function (dat) {

      var result = crmApi('Mailing', 'create', dat, true);
      console.log(result);
      result.success(function(data) {
        if (data.is_error == 0) {
          $scope.mailA.id = data.id;
          console.log("Mail a Id "+ $scope.mailA.id);
        }
      });
    };

    $scope.append_mails = function(){
      crmApi('MailingAB','create',{id:$scope.abId,mailing_id_a:$scope.mailA.id,mailing_id_b:$scope.mailB.id});
    };

    $scope.saveb = function (dat) {
     var flag =0;
      var result = crmApi('Mailing', 'create', dat, true);
      console.log(result);
      result.success(function(data) {
        if (data.is_error == 0) {
          $scope.mailB.id = data.id;
          console.log("Mail b Id "+ $scope.mailB.id);
          $scope.append_mails();


        }
      });

    };

    $scope.init = function (par) {

      $scope.whatnext = par.toString()
    };

    $scope.setdate = function (par) {
      console.log("called")
      console.log("av " + par)
      $scope.send_date = par;
      $scope.dt = par;
      $scope.apply();
    };


    $scope.incGroup = [];
    $scope.excGroup = [];

    $scope.create_abtest = function(){
      var result;
      $scope.currentABTest.testing_criteria_id=$scope.template.val;

      if($scope.abId =="" )
      result= crmApi('MailingAB','create',{testing_criteria_id: $scope.template.val});
      else{
        if (typeof $scope.currentABTest.mailing_id_a == 'undefined')
      result= crmApi('MailingAB','create',{id:$scope.abId,testing_criteria_id: $scope.template.val});
        else{
          result= crmApi('MailingAB','create',{id:$scope.abId,testing_criteria_id: $scope.template.val,mailing_id_a:$scope.currentABTest.mailing_id_a,mailing_id_b:$scope.currentABTest.mailing_id_b} );

        }

      }


      result.success(function(data) {
        if (data.is_error == 0) {
          $scope.abId = data.id;
          console.log("ID "+$scope.abId);
        }
      });
    };

    $scope.update_abtest = function(){

      $scope.currentABTest.declare_winning_time= $scope.currentABTest.date + " " + $scope.currentABTest.time ;

      result= crmApi('MailingAB','create',{id:$scope.abId,
                      testing_criteria_id: $scope.template.val,
                      mailing_id_a:$scope.currentABTest.mailing_id_a,
                      mailing_id_b:$scope.currentABTest.mailing_id_b,
                      winner_criteria_id : $scope.currentABTest.winner_criteria_id,
                      group_percentage: $scope.currentABTest.group_percentage,
                      declare_winning_time: $scope.currentABTest.declare_winning_time
                      } );

    };




    $scope.tmp = function (tst,aorb){
      if(aorb==1){
        $scope.mailA.msg_template_id=tst;
        console.log($scope.mailA.msg_template_id+ "sasas");
        if($scope.mailA.msg_template_id == null){
          $scope.mailA.body_html="";
        }
        else{
          for(var a in $scope.tmpList){

            if($scope.tmpList[a].id==$scope.mailA.msg_template_id){
              $scope.mailA.body_html=$scope.tmpList[a].msg_html;
            }
          }
        }
      }
      else if(aorb==2){

        $scope.mailB.msg_template_id=tst;
        console.log($scope.mailB.msg_template_id+ "sasas");
        if($scope.mailB.msg_template_id == null){
          $scope.mailB.body_html="";
        }
        else{
          for(var a in $scope.tmpList){

            if($scope.tmpList[a].id==$scope.mailB.msg_template_id){
              $scope.mailB.body_html=$scope.tmpList[a].msg_html;
            }
          }
        }

      }
      else {

        $scope.mailA.msg_template_id=tst;
        console.log($scope.mailA.msg_template_id+ "sasas");
        if($scope.mailA.msg_template_id == null){
          $scope.mailA.body_html="";
        }
        else{
          for(var a in $scope.tmpList){

            if($scope.tmpList[a].id==$scope.mailA.msg_template_id){
              $scope.mailA.body_html=$scope.tmpList[a].msg_html;
            }
          }
        }

        $scope.mailB.msg_template_id=tst;
        console.log($scope.mailB.msg_template_id+ "sasas");
        if($scope.mailB.msg_template_id == null){
          $scope.mailB.body_html="";
        }
        else{
          for(var a in $scope.tmpList){

            if($scope.tmpList[a].id==$scope.mailB.msg_template_id){
              $scope.mailB.body_html=$scope.tmpList[a].msg_html;
            }
          }
        }


      }
    };


  });

  crmMailingAB.directive('nexttab', function () {
    return {
      // Restrict it to be an attribute in this case
      restrict: 'A',

      priority: 500,
      // responsible for registering DOM listeners as well as updating the DOM
      link: function (scope, element, attrs) {

        $(element).parent().parent().parent().parent().tabs(scope.$eval(attrs.nexttab));
        var myarr = new Array(1, 2, 3)
        $(element).parent().parent().parent().parent().tabs({disabled: myarr});

        $(element).on("click", function () {
          if(scope.tab_val==0){
            scope.create_abtest();
          }
          else if(scope.tab_val == 2){
            scope.update_abtest();
            if(scope.currentABTest.winner_criteria_id==1){
              scope.winner_criteria="Open";
            }
            else if(scope.currentABTest.winner_criteria_id==2){
              scope.winner_criteria=" Total Unique Clicks";
            }
            else if(scope.currentABTest.winner_criteria_id==3){
                scope.winner_criteria="Total Clicks on a particular link";
              }
          }

          scope.tab_val = scope.tab_val + 1;

          scope.max_tab = Math.max(scope.tab_val, scope.max_tab);
          var myArray1 = new Array();
          for (var i = scope.max_tab + 1; i < 4; i++) {
            myArray1.push(i);
          }
          $(element).parent().parent().parent().parent().parent().tabs("option", "disabled", myArray1);
          $(element).parent().parent().parent().parent().parent().tabs("option", "active", scope.tab_val);
          scope.$apply();
          console.log("Adir");
        });
      }
    };
  });

  crmMailingAB.directive('prevtab', function () {
    return {
      // Restrict it to be an attribute in this case
      restrict: 'A',
      priority: 500,
      // responsible for registering DOM listeners as well as updating the DOM
      link: function (scope, element, attrs) {


        $(element).on("click", function () {
          var temp = scope.tab_val - 1;
          scope.tab_val = scope.tab_val - 1;

          console.log(temp);
          if (temp == 3) {

          }
          else {
            $(element).parent().parent().parent().parent().parent().tabs("option", "active", temp);
          }

          scope.$apply();

        });
      }
    };
  });

  crmMailingAB.directive('chsgroup', function () {
    return {
      restrict: 'AE',
      link: function (scope, element, attrs) {
        function format(item) {
          if (!item.id) {
            // return `text` for optgroup
            return item.text;
          }
          // return item template
          var a = item.id.split(" ");
          if (a[1] == "group" && a[2] == "include") {
            return "<img src='../../sites/all/modules/civicrm/i/include.jpeg' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/group.png' height=12 width=12/>" + item.text;
          }
          if (a[1] == "group" && a[2] == "exclude") {
            return "<img src='../../sites/all/modules/civicrm/i/Error.gif' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/group.png' height=12 width=12/>" + item.text;
          }
          if (a[1] == "mail" && a[2] == "include") {
            return "<img src='../../sites/all/modules/civicrm/i/include.jpeg' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/EnvelopeIn.gif' height=12 width=12/>" + item.text;
          }
          if (a[1] == "mail" && a[2] == "exclude") {
            return "<img src='../../sites/all/modules/civicrm/i/Error.gif' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/EnvelopeIn.gif' height=12 width=12/>" + item.text;
          }
        }

        $(element).select2({
          width: "400px",
          placeholder: "Select the groups you wish to include",
          formatResult: format,
          formatSelection: format,
          escapeMarkup: function (m) {
            return m;
          }
        });

        $(element).on('select2-selecting', function (e) {
          var a = e.val.split(" ");
          var l = a.length;
          if (a[2] == "include") {
            var str = "";
            for (i = 3; i < l; i++) {
              str += a[i];
              str += " ";
            }
            scope.incGroup.push(str);
            scope.$apply();
          }

          else {
            var str = "";
            for (i = 3; i < l; i++) {
              str += a[i];
              str += " ";
            }

            scope.excGroup.push(str);
            scope.$apply();
          }

        });
        $(element).on("select2-removed", function (e) {
          if (e.val.split(" ")[2] == "exclude") {
            var index = scope.excGroup.indexOf(e.val.split(" ")[3]);
            scope.excGroup.splice(index, 1);
            scope.$apply();
          }
          else {
            var index = scope.incGroup.indexOf(e.val.split(" ")[3]);
            scope.incGroup.splice(index, 1);
            scope.$apply();
          }
        });
      }
    };

  });

  crmMailingAB.directive('groupselect',function(){
    return {
      restrict : 'AE',
      link: function(scope,element, attrs){
        $(element).select2({width:"200px", data: mltokens, placeholder:"Insert Token"});


        $(element).on('select2-selecting', function(e) {
          scope.$evalAsync('_resetSelection()');console.log(mltokens);
          /* if(scope.currentMailing.body_html == null){
           scope.currentMailing.body_html = e.val;
           }
           else
           scope.currentMailing.body_html = scope.currentMailing.body_html + e.val;
           */
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
          scope.$apply();
          e.preventDefault();
        })

      }
    };

  });

  crmMailingAB.directive('sliderbar', function () {
    return{
      restrict: 'AE',
      link: function (scope, element, attrs) {
        if(typeof scope.currentABTest.group_percentage != 'undefined'){
          console.log("Yay");
          $(element).slider({value:scope.currentABTest.group_percentage});
        }
        $(element).slider({min: 1});
        $(element).slider({
          slide: function (event, ui) {
            scope.currentABTest.group_percentage = ui.value;
            scope.$apply();
          }
        });
      }
    };
  });

  crmMailingAB.directive('tpmax', function () {
    return {
      restrict: 'E',
      link: function (scope, element, attr) {
        scope.$watch('automated', function (val) {
          if (val == "Yes") {
            $(element).dialog({
              title: 'Automated A/B Testing',
              width: 800,
              height: 600,
              closed: false,
              cache: false,
              modal: true
            });
          }
        });

        $(element).find("#closebutton").on("click", function () {
          $(element).dialog("close");
        });
      }
    };
  });

  crmMailingAB.directive('numbar', function () {
    return{
      restrict: 'AE',
      link: function (scope, element, attrs) {
        $(element).spinner({max: attrs.numbar, min: 0});
      }
    };
  });

  crmMailingAB.directive('datepick', function () {
    return {


      restrict: 'AE',
      link: function (scope, element, attrs) {
        $(element).datepicker({
          dateFormat: "yy-mm-dd",
          onSelect: function (date) {
            $(".ui-datepicker a").removeAttr("href");

            scope.scheddate.date = date.toString();
            scope.$apply();
            console.log(scope.scheddate.date);

          }


        });
      }
    };
  });

  crmMailingAB.directive('submitform', function () {
    return {
      restrict: 'A',
      priority: 1000,
      link: function (scope, element, attrs) {
        $(element).on("click", function () {

          console.log("clicked");
          scope.savea({
            id: scope.mailA.id,
            name: "Aditya Nambiar",
            visibility:  scope.mailA.visibility,
            created_id: 1,
            subject: scope.mailA.subject,
            msg_template_id: scope.mailA.msg_template_id==null ? "" : scope.mailA.msg_template_id,
            open_tracking: scope.mailA.open_tracking,
            url_tracking: scope.mailA.url_tracking,
            forward_replies: scope.mailA.forward_replies,
            auto_responder: scope.mailA.auto_responder,
            from_name: scope.mailA.from_name,
            from_email: scope.mailA.from_email,
            replyto_email: scope.mailA.replyto_email,
            unsubscribe_id: scope.mailA.unsubscribe_id,
            resubscribe_id: scope.mailA.resubscribe_id,
            body_html: scope.mailA.body_html,
            body_text: scope.mailA.body_text,
            scheduled_date: scope.mailA.scheduled_date,
            scheduled_id: scope.mailA.scheduled_id,
            campaign_id:	scope.mailA.campaign_id==null ? "" : scope.mailA.campaign_id,
            header_id:	scope.mailA.header_id,
            footer_id: scope.mailA.footer_id,

            is_completed: scope.mailA.is_completed
          });
          console.log("Truth " + scope.whatnext)

          if (scope.whatnext == "3") {
            console.log("sdf");

              scope.mailB.name= scope.mailA.name;
              scope.mailB.visibility=  scope.mailA.visibility;
              scope.mailB.created_id= scope.mailA.created_id;
              scope.mailB.subject= scope.mailA.subject;
              scope.mailB.msg_template_id= scope.mailA.msg_template_id==null ? "" : scope.mailA.msg_template_id;
              scope.mailB.open_tracking= scope.mailA.open_tracking;
              scope.mailB.url_tracking= scope.mailA.url_tracking;
              scope.mailB.forward_replies= scope.mailA.forward_replies;
              scope.mailB.auto_responder= scope.mailA.auto_responder;
              scope.mailB.from_name= scope.mailA.from_name;
              scope.mailB.replyto_email= scope.mailA.replyto_email;
              scope.mailB.unsubscribe_id= scope.mailA.unsubscribe_id;
              scope.mailB.resubscribe_id= scope.mailA.resubscribe_id;
              scope.mailB.body_html= scope.mailA.body_html;
              scope.mailB.body_text= scope.mailA.body_text;
              scope.mailB.scheduled_date= scope.mailA.scheduled_date;
              scope.mailB.scheduled_id= scope.mailA.scheduled_id;
              scope.mailB.campaign_id=	scope.mailA.campaign_id==null ? "" : scope.mailA.campaign_id;
              scope.mailB.header_id=	scope.mailA.header_id;
              scope.mailB.footer_id=	scope.mailA.footer_id;

              scope.mailB.is_completed= scope.mailA.is_completed;

          }
          else {
            if (scope.whatnext == "2") {
              scope.mailB.fromEmail = scope.mailA.fromEmail;
              scope.mailB.name= scope.mailA.name;
              scope.mailB.visibility=  scope.mailA.visibility;
              scope.mailB.created_id= scope.mailA.created_id;
              scope.mailB.msg_template_id= scope.mailA.msg_template_id==null ? "" : scope.mailA.msg_template_id;
              scope.mailB.open_tracking= scope.mailA.open_tracking;
              scope.mailB.url_tracking= scope.mailA.url_tracking;
              scope.mailB.forward_replies= scope.mailA.forward_replies;
              scope.mailB.auto_responder= scope.mailA.auto_responder;
              scope.mailB.from_name= scope.mailA.from_name;
              scope.mailB.replyto_email= scope.mailA.replyto_email;
              scope.mailB.unsubscribe_id= scope.mailA.unsubscribe_id;
              scope.mailB.resubscribe_id= scope.mailA.resubscribe_id;
              scope.mailB.body_html= scope.mailA.body_html;
              scope.mailB.body_text= scope.mailA.body_text;
              scope.mailB.scheduled_date= scope.mailA.scheduled_date;
              scope.mailB.scheduled_id= scope.mailA.scheduled_id;
              scope.mailB.campaign_id=	scope.mailA.campaign_id==null ? "" : scope.mailA.campaign_id;
              scope.mailB.header_id=	scope.mailA.header_id;
              scope.mailB.footer_id=	scope.mailA.footer_id;

              scope.mailB.is_completed= scope.mailA.is_completed;

            }
          }


          scope.saveb({

            id: scope.mailB.id,
            name: "Aditya Nambiar",
            visibility:  scope.mailB.visibility,
            created_id: 1,
            subject: scope.mailB.subject,
            msg_template_id: scope.mailB.msg_template_id==null ? "" : scope.mailB.msg_template_id,
            open_tracking: scope.mailB.open_tracking,
            url_tracking: scope.mailB.url_tracking,
            forward_replies: scope.mailB.forward_replies,
            auto_responder: scope.mailB.auto_responder,
            from_name: scope.mailB.from_name,
            from_email: scope.mailB.from_email,
            replyto_email: scope.mailB.replyto_email,
            unsubscribe_id: scope.mailB.unsubscribe_id,
            resubscribe_id: scope.mailB.resubscribe_id,
            body_html: scope.mailB.body_html,
            body_text: scope.mailB.body_text,
            scheduled_date: scope.mailB.scheduled_date,
            scheduled_id: scope.mailB.scheduled_id,
            campaign_id:	scope.mailB.campaign_id==null ? "" : scope.mailB.campaign_id,
            header_id:	scope.mailB.header_id,
            footer_id:	scope.mailB.footer_id,

            is_completed: scope.mailA.is_completed

          });


        });
      }
    };

  });



  crmMailingAB.directive('nextbutton', function () {
    return {
      restrict: 'AE',
      replace: 'true',
      template: '<div class="crm-submit-buttons" id="campaignbutton">' +
        '<div class = "crm-button crm-button-type-upload crm-button_qf_Contact_upload_view"   >' +
        '<input type="submit" value="Next"  id="campaignbutton _qf_Contact_upload_view-top" class="btn btn-primary" nexttab={{tab_val}}>' +
        '</div></div>'

    };
  });

  crmMailingAB.directive('cancelbutton', function () {
    return {
      restrict: 'AE',
      replace: 'true',
      template: '<div class="crm-submit-buttons" id="campaignbutton">' +
        '<div class = "crm-button crm-button-type-upload crm-button_qf_Contact_upload_view"   >' +
        '<input type="submit" value="Cancel"  id="campaignbutton _qf_Contact_upload_view-top" class="btn btn-primary" >' +
        '</div></div>'

    };
  });

  crmMailingAB.directive('chsdate',function(){
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

  crmMailingAB.directive('prevbutton', function () {
    return {
      restrict: 'AE',
      replace: 'true',
      template: '<div class="crm-submit-buttons" >' +
        '<div class = "crm-button crm-button-type-upload crm-button_qf_Contact_upload_view"   >' +
        '<input type="submit" value="Previous"  id="campaignbutton _qf_Contact_upload_view-top" class="btn btn-primary" prevtab={{tab_val}}>' +
        '</div></div>'

    };
  });


})(angular, CRM.$, CRM._);

