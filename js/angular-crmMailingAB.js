/**
 * Created by aditya on 6/12/14.
 */
(function (angular, $, _) {

  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/abtesting/' + relPath;
  };
  var crmMailingAB = angular.module('crmMailingAB', ['ngRoute', 'ui.utils', 'ngSanitize']);

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
        controller: 'ABListingCtrl',
        resolve: {
          mailingABList: function ($route, crmApi) {
            return crmApi('MailingAB', 'get', {});
          }
        }
      });
      $routeProvider.when('/mailing/abtesting/report/:id', {
        templateUrl: partialUrl('report.html'),
        controller: 'ReportCtrl',
        resolve: {
          selectedABTest: function ($route, crmApi) {
            return crmApi('MailingAB', 'getsingle', {id: $route.current.params.id});
          }
        }
      });
      $routeProvider.when('/mailing/abtesting/:id', {
        templateUrl: partialUrl('main.html'),
        controller: 'crmABTestingTabsCtrl',
        resolve: {
          selectedABTest: function ($route, crmApi) {
            if ($route.current.params.id !== 'new') {

              return crmApi('MailingAB', 'getsingle', {id: $route.current.params.id});
            }
            else {
              //created_id has been set to my id. Does not save without created_id. Needs to made generic based on the user
              return { just_created: "1"
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
  /**
   * This controler lists the existing ABtests
   * used on /partials/abtesting/list.html
   * @returns mailingABList - object that contains the existing AB mailings
   * @returns testing_criteria - array that has the name of the different test types
   */
  crmMailingAB.controller('ABListingCtrl', function ($scope, crmApi, mailingABList) {
    $scope.mailingABList = mailingABList.values;
    $scope.testing_criteria = {
      '1': "Subject lines",
      '2': "From names",
      '3': "Two different emails"
    };
  });

  crmMailingAB.controller('crmABTestingTabsCtrl', function ($scope, crmApi, selectedABTest, $sce) {
    $scope.partialUrl = partialUrl;
    $scope.abId = "";
    $scope.whatnext = 2;
    $scope.currentABTest = selectedABTest;
    $scope.groups = CRM.crmMailing.groupNames;
    $scope.mailList = CRM.crmMailing.civiMails;
    $scope.eMailing = CRM.crmMailing.emailAdd;
    $scope.tmpList = CRM.crmMailing.mesTemplate;
    $scope.mailingGrp = CRM.crmMailing.mailGrp;
    $scope.headerfooter = CRM.crmMailing.headerfooterList;
    $scope.sparestuff = {};
    $scope.sparestuff.emailadd = "";
    $scope.sparestuff.winnercriteria = "";
    $scope.sparestuff.isnew = false;
    $scope.sparestuff.allgroups = "";
    $scope.mailid = [];
    $scope.preventsubmit = false;

    if ($scope.currentABTest.declare_winning_time != null) {
      $scope.ans = $scope.currentABTest.declare_winning_time.split(" ");
      $scope.currentABTest.date = $scope.ans[0];
      $scope.currentABTest.time = $scope.ans[1];
    }
    $scope.token = [];

    if ($scope.currentABTest.just_created != 1) {
      $scope.abId = $scope.currentABTest.id;
      $scope.sparestuff.isnew = false;

      var abmailA = crmApi('Mailing', 'getsingle', {id: $scope.currentABTest.mailing_id_a});
      var abmailB = crmApi('Mailing', 'getsingle', {id: $scope.currentABTest.mailing_id_b});
      var abmailC = crmApi('Mailing', 'getsingle', {id: $scope.currentABTest.mailing_id_c});
      abmailA.success(function (data) {
        if (data.is_error == 0) {
          $scope.mailA = data;
        }
      });
      abmailB.success(function (data) {
        if (data.is_error == 0) {
          $scope.mailB = data;
        }
      });
      abmailC.success(function (data) {
        if (data.is_error == 0) {
          $scope.mailC = data;
        }
      });
    }
    else {
      $scope.sparestuff.isnew = true;
      $scope.mailA = {};
      $scope.mailB = {};
      $scope.mailC = {};
    }

    $scope.sendtest = false;
    if (typeof $scope.mailA == 'undefined') {
      $scope.mailA = {};
    }
    if (typeof $scope.mailB == 'undefined') {
      $scope.mailB = {};
    }
    if (typeof $scope.mailB == 'undefined') {
      $scope.mailC = {};
    }

    $scope.templates =
      [
        { name: 'Subject Lines', url: partialUrl('subject_lines.html'), val: 1},
        { name: 'From Name', url: partialUrl('from_name.html'), val: 2},
        { name: 'Two different Emails', url: partialUrl('two_emails.html'), val: 3}
      ];

    if ($scope.currentABTest.just_created != 1) {
      $scope.sparestuff.template = $scope.templates[$scope.currentABTest.testing_criteria_id - 1];
    }
    else {
      $scope.sparestuff.template = $scope.templates[0];
    }


    $scope.deliberatelyTrustDangerousSnippeta = function () {
      return $sce.trustAsHtml($scope.sparestuff.previewa);
    };

    $scope.deliberatelyTrustDangerousSnippetb = function () {
      return $sce.trustAsHtml($scope.sparestuff.previewb);
    };

    $scope.tab_val = 0;
    $scope.max_tab = ($scope.sparestuff.isnew == true) ? 0 : 4;

    $scope.campaign_clicked = function () {
      if ($scope.max_tab >= 0) {
        $scope.tab_val = 0;
      }
    };

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

    $scope.preview = false;
    $scope.slide_value = 0;

    $scope.setifyes = function (val) {
      $scope.ifyes = val == 1;
    };

    /*    $scope.reply = function () {
     if ($scope.trackreplies == 0) {
     $scope.trackreplies = 1;
     }
     else {
     $scope.trackreplies = 0;
     $scope.mailA.forward_replies = 0;
     $scope.mailA.auto_responder = 0;
     }
     }
     */
    $scope.isAuto = function (au) {
      return au.component_type == "Reply";
    };

    $scope.trackr = function (trackreplies) {
      return trackreplies == "1";
    };

    $scope.sendTestMailing = function () {
      $scope.sendtest = true;
    };

    $scope.isHeader = function (hf) {
      return hf.component_type == "Header";
    };
    //filter so we only get footers from mailing component
    $scope.isFooter = function (f) {
      return f.component_type == "Footer";
    };

    $scope.send_date = "01/01/2000";
    $scope.dt = "";

    $scope.savea = function (dat) {

      var result = crmApi('Mailing', 'create', dat, true);
      result.success(function (data) {
        if (data.is_error == 0) {
          $scope.mailA.id = data.id;
          $scope.currentABTest.mailing_id_a = $scope.mailA.id;
        }
      });
    };

    $scope.append_mails = function () {
      crmApi('MailingAB', 'create', {
        id: $scope.abId,
        mailing_id_a: $scope.mailA.id,
        mailing_id_b: $scope.mailB.id,
        mailing_id_c: $scope.mailC.id
      });
      $scope.currentABTest.id = $scope.abId;
    };

    $scope.saveb = function (dat) {
      var result = crmApi('Mailing', 'create', dat, true);
      result.success(function (data) {
        if (data.is_error == 0) {
          $scope.mailB.id = data.id;
          $scope.currentABTest.mailing_id_b = $scope.mailB.id;
          //$scope.append_mails();
        }
      });
    };

    $scope.savec = function (dat) {
      var result = crmApi('Mailing', 'create', dat, true);

      result.success(function (data) {
        if (data.is_error == 0) {
          $scope.mailC.id = data.id;
          $scope.currentABTest.mailing_id_c = $scope.mailC.id;
          $scope.append_mails();
        }
      });
    };

    $scope.sparestuff.previewa = "";
    $scope.pre = function () {
      $scope.preview = true;
    };

    $scope.init = function (par) {
      if (par == "3") {
        $scope.sparestuff.template.url = partialUrl('from_name.html');
      }
      else {
        if (par == "2") {
          $scope.sparestuff.template.url = partialUrl('subject_lines.html');
        }
        else {
          $scope.sparestuff.template.url = partialUrl('two_emails.html');
        }
      }
      $scope.whatnext = par.toString();
    };

    $scope.tab_upd = function () {
      $scope.tab_val = $scope.tab_val + 1;
      $scope.max_tab = Math.max($scope.tab_val, $scope.max_tab);
    };

    $scope.tab_upd_dec = function () {
      $scope.tab_val = $scope.tab_val - 1;
    };

    $scope.setdate = function (par) {
      $scope.send_date = par;
      $scope.dt = par;
      $scope.apply();
    };

    $scope.testmailid = "";
    $scope.incGroup = [];
    $scope.excGroup = [];
    $scope.incGroupids = [];
    $scope.excGroupids = [];
    $scope.tp1 = {};
    $scope.create_abtest = function () {
      var result;
      $scope.currentABTest.testing_criteria_id = $scope.sparestuff.template.val;

      if ($scope.abId == "") {
        result = crmApi('MailingAB', 'create', {name: $scope.currentABTest.name, testing_criteria_id: $scope.sparestuff.template.val});
      }
      else {
        if (typeof $scope.currentABTest.mailing_id_a == 'undefined') {
          result = crmApi('MailingAB', 'create', {name: $scope.currentABTest.name, id: $scope.abId, testing_criteria_id: $scope.sparestuff.template.val});
        }
        else {
          result = crmApi('MailingAB', 'create', {name: $scope.currentABTest.name, id: $scope.abId, testing_criteria_id: $scope.sparestuff.template.val, mailing_id_a: $scope.currentABTest.mailing_id_a, mailing_id_b: $scope.currentABTest.mailing_id_b});
        }
      }

      result.success(function (data) {
        if (data.is_error == 0) {
          $scope.abId = data.id;
        }
      });
    };
    $scope.tokenfunc = function (elem, e, chng) {
      var msg = document.getElementById(elem).value;
      var cursorlen = document.getElementById(elem).selectionStart;
      var textlen = msg.length;
      document.getElementById(elem).value = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
      chng = msg.substring(0, cursorlen) + e.val + msg.substring(cursorlen, textlen);
      var cursorPos = (cursorlen + e.val.length);
      document.getElementById(elem).selectionStart = cursorPos;
      document.getElementById(elem).selectionEnd = cursorPos;
      document.getElementById(elem).focus();
    };

    $scope.sparestuff.ingrps = "";
    $scope.sparestuff.excgrps = "";
    $scope.a_b_update = function () {
      $scope.tp1.include = $scope.incGroupids;
      $scope.tp1.exclude = $scope.excGroupids;
      console.log($scope.tp1);
      crmApi('MailingAB', 'recipients_update', {
        id: $scope.currentABTest.id,
        groups: $scope.tp1
      });

      var resulta = crmApi('Mailing', 'preview', {id: $scope.currentABTest.mailing_id_a});

      resulta.success(function (data) {
        if (data.is_error == 0) {
          $scope.sparestuff.previewa = data.values.html;
        }
      });

      resulta = crmApi('Mailing', 'preview', {id: $scope.currentABTest.mailing_id_b});

      resulta.success(function (data) {
        if (data.is_error == 0) {
          $scope.sparestuff.previewb = data.values.html;
        }
      });

      $scope.startabtest = function () {
        if (typeof $scope.sparestuff.date == 'undefined') {
          $scope.sparestuff.date = 'now';
        }
        crmApi('MailingAB', 'send_mail', {id: $scope.abId,
          scheduled_date: $scope.sparestuff.date, scheduled_date_time: $scope.currentABTest.latertime});
      };

      angular.forEach($scope.incGroup, function (value) {
        $scope.sparestuff.ingrps += value.toString() + ", ";
      });
      angular.forEach($scope.excGroup, function (value) {
        $scope.sparestuff.excgrps += value.toString() + ", ";
      });
      if ($scope.sparestuff.ingrps.length != 0) {
        $scope.sparestuff.ingrps = $scope.sparestuff.ingrps.substr(0, $scope.sparestuff.ingrps.length - 2);
      }
      if ($scope.sparestuff.excgrps.length != 0) {
        $scope.sparestuff.excgrps = $scope.sparestuff.excgrps.substr(0, $scope.sparestuff.excgrps.length - 2);
      }
    };

    $scope.update_abtest = function () {
      $scope.currentABTest.declare_winning_time = $scope.currentABTest.date + " " + $scope.currentABTest.time;
      crmApi('MailingAB', 'create', {
        id: $scope.abId,
        testing_criteria_id: $scope.sparestuff.template.val,
        mailing_id_a: $scope.currentABTest.mailing_id_a,
        mailing_id_b: $scope.currentABTest.mailing_id_b,
        mailing_id_c: $scope.currentABTest.mailing_id_c,
        specific_url: $scope.currentABTest.acturl,
        winner_criteria_id: $scope.currentABTest.winner_criteria_id,
        group_percentage: $scope.currentABTest.group_percentage,
        declare_winning_time: $scope.currentABTest.declare_winning_time
      });
    };
    $scope.currentABTest.latertime = "";
    $scope.tmp = function (tst, aorb) {
      if (aorb == 1) {
        $scope.mailA.msg_template_id = tst;
        if ($scope.mailA.msg_template_id == null) {
          $scope.mailA.body_html = "";
          $scope.mailA.subject = "";
        }
        else {
          for (var a in $scope.tmpList) {
            if ($scope.tmpList[a].id == $scope.mailA.msg_template_id) {
              $scope.mailA.body_html = $scope.tmpList[a].msg_html;
              if (typeof $scope.mailA.subject == 'undefined' || $scope.mailA.subject.length == 0) {
                $scope.mailA.subject = $scope.tmpList[a].msg_subject;
              }
            }
          }
        }
      }
      else {
        if (aorb == 2) {
          $scope.mailB.msg_template_id = tst;
          if ($scope.mailB.msg_template_id == null) {
            $scope.mailB.body_html = "";
            $scope.mailB.subject = "";
          }
          else {
            for (var a in $scope.tmpList) {
              if ($scope.tmpList[a].id == $scope.mailB.msg_template_id) {
                $scope.mailB.body_html = $scope.tmpList[a].msg_html;
                if (typeof $scope.mailB.subject == 'undefined' || $scope.mailB.subject.length == 0) {
                  $scope.mailB.subject = $scope.tmpList[a].msg_subject;
                }

              }
            }
          }
        }
        else {
          $scope.mailA.msg_template_id = tst;
          if ($scope.mailA.msg_template_id == null) {
            $scope.mailA.body_html = "";
            $scope.mailA.subject = "";
          }
          else {
            for (var a in $scope.tmpList) {
              if ($scope.tmpList[a].id == $scope.mailA.msg_template_id) {
                $scope.mailA.body_html = $scope.tmpList[a].msg_html;
                if (typeof $scope.mailA.subject == 'undefined' || $scope.mailA.subject.length == 0) {
                  $scope.mailA.subject = $scope.tmpList[a].msg_subject;
                }
              }
            }
          }

          $scope.mailB.msg_template_id = tst;
          if ($scope.mailB.msg_template_id == null) {
            $scope.mailB.body_html = "";
            $scope.mailB.subject = "";

          }
          else {
            for (var a in $scope.tmpList) {
              if ($scope.tmpList[a].id == $scope.mailB.msg_template_id) {
                $scope.mailB.body_html = $scope.tmpList[a].msg_html;
                if (typeof $scope.mailB.subject == 'undefined' || $scope.mailB.subject.length == 0) {
                  $scope.mailB.subject = $scope.tmpList[a].msg_subject;
                }

              }
            }
          }
        }
      }
    };

    /*$scope.tmp = function (tst){
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
     };*/


    $scope.$watch('preview', function () {
      if ($scope.preview == true) {
        $('#prevmail').dialog({
          title: 'Preview Mailing',
          width: 1080,
          height: 700,
          closed: false,
          cache: false,
          modal: true,
          position: {
            my: 'left',
            at: 'top',
            of: $(".crmABTestingAllTabs")
          },

          close: function () {
            $scope.preview = false;
            $scope.$apply();
          }
        });

        $("#prevmail").dialog('option', 'position', [300, 50]);
      }

    }, true);

    $scope.call = function () {
      $scope.$apply();
      crmApi('Mailing', 'send_test', {
        mailing_id: $scope.currentABTest.mailing_id_a,
        test_email: $scope.sparestuff.emailadd
      });

      crmApi('Mailing', 'send_test', {
        mailing_id: $scope.currentABTest.mailing_id_b,
        test_email: $scope.sparestuff.emailadd
      })
    };

    $scope.$watch('sendtest', function () {
      if ($scope.sendtest == true) {
        $('#sendtest').dialog({
          title: 'Send Test Mails',
          width: 300,
          height: 150,
          closed: false,
          cache: false,
          modal: true,
          buttons: {
            'Send': function () {
              $scope.call();
              $scope.sendtest = false;
              $('#sendtest').dialog("close");

            }
          },
          close: function () {
            $scope.sendtest = false;
            $scope.$apply()
          }
        });
      }
    });
  });


  crmMailingAB.directive('nexttab', function () {
    return {
      // Restrict it to be an attribute in this case
      restrict: 'A',
      priority: 500,
      // responsible for registering DOM listeners as well as updating the DOM
      link: function (scope, element, attrs) {

        var tabselector = $(".crmABTestingAllTabs");
        tabselector.tabs(scope.$eval(attrs.nexttab));

        // disable remaining tabs
        if (scope.sparestuff.isnew == true) {
          tabselector.tabs({disabled: [1, 2, 3]});
        }

        $(element).on("click", function () {
          if (scope.tab_val == 0) {

            scope.create_abtest();


          }
          else {
            if (scope.tab_val == 2) {
              scope.update_abtest();
              if (scope.currentABTest.winner_criteria_id == 1) {
                scope.sparestuff.winnercriteria = "Open";
                scope.$apply();
              }
              else {
                if (scope.currentABTest.winner_criteria_id == 2) {
                  scope.sparestuff.winnercriteria = " Total Unique Clicks";
                  scope.$apply();
                }
                else {
                  if (scope.currentABTest.winner_criteria_id == 3) {
                    scope.sparestuff.winnercriteria = "Total Clicks on a particular link";
                    scope.$apply();
                  }
                }
              }
              scope.a_b_update();
            }
          }

          scope.tab_upd();

          var myArray1 = [];
          for (var i = scope.max_tab + 1; i < 4; i++) {
            myArray1.push(i);
          }
          tabselector.tabs("option", "disabled", myArray1);
          tabselector.tabs("option", "active", scope.tab_val);
          scope.$apply();
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
          scope.tab_upd_dec();
          scope.$apply();
          if (temp != 3) {
            $(".crmABTestingAllTabs").tabs("option", "active", temp);
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
          if (a[1] == "civicrm_group" && a[2] == "include") {
            return "<img src='../../sites/all/modules/civicrm/i/include.jpeg' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/group.png' height=12 width=12/>" + item.text;
          }
          if (a[1] == "civicrm_group" && a[2] == "exclude") {
            return "<img src='../../sites/all/modules/civicrm/i/Error.gif' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/group.png' height=12 width=12/>" + item.text;
          }
          if (a[1] == "civicrm_mailing" && a[2] == "include") {
            return "<img src='../../sites/all/modules/civicrm/i/include.jpeg' height=12 width=12/>" + " " + "<img src='../../sites/all/modules/civicrm/i/EnvelopeIn.gif' height=12 width=12/>" + item.text;
          }
          if (a[1] == "civicrm_mailing" && a[2] == "exclude") {
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
        }).select2("data", scope.sparestuff.allgroups);


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
            scope.incGroupids.push(a[0]);
            scope.$apply();
          }

          else {
            var str = "";
            for (i = 3; i < l; i++) {
              str += a[i];
              str += " ";
            }

            scope.excGroup.push(str);
            scope.excGroupids.push(a[0]);
            scope.$apply();
          }

          scope.$apply();

        });
        $(element).on("select2-removed", function (e) {
          if (e.val.split(" ")[2] == "exclude") {
            var excIndex = scope.excGroup.indexOf(e.val.split(" ")[3]);
            scope.excGroup.splice(excIndex, 1);
            scope.excGroupids.splice(excIndex, 1);
            scope.$apply();
          }
          else {
            var incIndex = scope.incGroup.indexOf(e.val.split(" ")[3]);
            scope.incGroup.splice(incIndex, 1);
            scope.incGroupids.splice(incIndex, 1);
            scope.$apply();
          }

          scope.$apply();
        });
      }
    };
  });

  crmMailingAB.directive('sliderbar', function () {
    return{
      restrict: 'AE',
      link: function (scope, element, attrs) {
        if (typeof scope.currentABTest.group_percentage != 'undefined') {
          $(element).slider({value: scope.currentABTest.group_percentage});
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

  crmMailingAB.directive('datepick', function () {
    return {
      restrict: 'AE',
      link: function (scope, element, attrs) {
        $(element).datepicker({
          dateFormat: "dd-mm-yy",
          onSelect: function (date) {
            $(".ui-datepicker a").removeAttr("href");
            scope.sparestuff.date = date.toString();
            scope.$apply();
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
          scope.savea({
            id: scope.mailA.id,
            name: "mailing a",
            visibility: scope.mailA.visibility,
            created_id: 1,
            subject: scope.mailA.subject,
            msg_template_id: scope.mailA.msg_template_id == null ? "" : scope.mailA.msg_template_id,
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
            campaign_id: scope.mailA.campaign_id == null ? "" : scope.mailA.campaign_id,
            header_id: scope.mailA.header_id,
            footer_id: scope.mailA.footer_id,
            is_completed: scope.mailA.is_completed
          });

          if (scope.whatnext == "3") {
            scope.mailB.name = scope.mailA.name;
            scope.mailB.visibility = scope.mailA.visibility;
            scope.mailB.created_id = scope.mailA.created_id;
            scope.mailB.subject = scope.mailA.subject;
            scope.mailB.msg_template_id = scope.mailA.msg_template_id == null ? "" : scope.mailA.msg_template_id;
            scope.mailB.open_tracking = scope.mailA.open_tracking;
            scope.mailB.url_tracking = scope.mailA.url_tracking;
            scope.mailB.forward_replies = scope.mailA.forward_replies;
            scope.mailB.auto_responder = scope.mailA.auto_responder;
            scope.mailB.from_name = scope.mailA.from_name;
            scope.mailB.replyto_email = scope.mailA.replyto_email;
            scope.mailB.unsubscribe_id = scope.mailA.unsubscribe_id;
            scope.mailB.resubscribe_id = scope.mailA.resubscribe_id;
            scope.mailB.body_html = scope.mailA.body_html;
            scope.mailB.body_text = scope.mailA.body_text;
            scope.mailB.scheduled_id = scope.mailA.scheduled_id;
            scope.mailB.campaign_id = scope.mailA.campaign_id == null ? "" : scope.mailA.campaign_id;
            scope.mailB.header_id = scope.mailA.header_id;
            scope.mailB.footer_id = scope.mailA.footer_id;
            scope.mailB.is_completed = scope.mailA.is_completed;
          }
          else {
            if (scope.whatnext == "2") {
              scope.mailB.fromEmail = scope.mailA.fromEmail;
              scope.mailB.name = scope.mailA.name;
              scope.mailB.visibility = scope.mailA.visibility;
              scope.mailB.created_id = scope.mailA.created_id;
              scope.mailB.msg_template_id = scope.mailA.msg_template_id == null ? "" : scope.mailA.msg_template_id;
              scope.mailB.open_tracking = scope.mailA.open_tracking;
              scope.mailB.url_tracking = scope.mailA.url_tracking;
              scope.mailB.forward_replies = scope.mailA.forward_replies;
              scope.mailB.auto_responder = scope.mailA.auto_responder;
              scope.mailB.from_name = scope.mailA.from_name;
              scope.mailB.replyto_email = scope.mailA.replyto_email;
              scope.mailB.unsubscribe_id = scope.mailA.unsubscribe_id;
              scope.mailB.resubscribe_id = scope.mailA.resubscribe_id;
              scope.mailB.body_html = scope.mailA.body_html;
              scope.mailB.body_text = scope.mailA.body_text;
              scope.mailB.scheduled_id = scope.mailA.scheduled_id;
              scope.mailB.campaign_id = scope.mailA.campaign_id == null ? "" : scope.mailA.campaign_id;
              scope.mailB.header_id = scope.mailA.header_id;
              scope.mailB.footer_id = scope.mailA.footer_id;
              scope.mailB.is_completed = scope.mailA.is_completed;
            }
          }
          scope.saveb({
            id: scope.mailB.id,
            name: "mailing b",
            visibility: scope.mailB.visibility,
            created_id: 1,
            subject: scope.mailB.subject,
            msg_template_id: scope.mailB.msg_template_id == null ? "" : scope.mailB.msg_template_id,
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
            scheduled_id: scope.mailB.scheduled_id,
            campaign_id: scope.mailB.campaign_id == null ? "" : scope.mailB.campaign_id,
            header_id: scope.mailB.header_id,
            footer_id: scope.mailB.footer_id,
            is_completed: scope.mailA.is_completed
          });

          scope.savec({
            id: scope.mailC.id,
            name: "mailing c",
            visibility: scope.mailB.visibility,
            created_id: 1,
            subject: scope.mailB.subject,
            msg_template_id: scope.mailB.msg_template_id == null ? "" : scope.mailB.msg_template_id,
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
            campaign_id: scope.mailB.campaign_id == null ? "" : scope.mailB.campaign_id,
            header_id: scope.mailB.header_id,
            footer_id: scope.mailB.footer_id,
            is_completed: scope.mailA.is_completed,
            'api.mailing_job.create': 0
          });
        });
      }
    };
  });

  crmMailingAB.directive('chsdate', function () {
    return {
      restrict: 'AE',
      link: function (scope, element, attrs) {
        $(element).datepicker({
          dateFormat: "yy-mm-dd",
          onSelect: function (date) {
            $(".ui-datepicker a").removeAttr("href");
            scope.currentABTest.date = date.toString();
            scope.$apply();
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

  crmMailingAB.directive('groupselect', function () {
    return {
      restrict: 'AE',
      link: function (scope, element, attrs) {
        $(element).select2({width: "200px", data: CRM.crmMailing.mailTokens, placeholder: "Insert Token"});
        $(element).on('select2-selecting', function (e) {

          scope.$evalAsync('_resetSelection()');
          var a = $(element).attr('id');
          if (a == "htgroupcompose") {
            scope.tokenfunc("body_html", e, scope.mailA.body_html);
          }
          else {
            if (a == "htgroupcomposetwob") {
              scope.tokenfunc("twomailbbody_html", e, scope.mailB.body_html);
            }
            else {
              if (a == "htgroupcomposetwoa") {
                scope.tokenfunc("twomailabody_html", e, scope.mailA.body_html);
              }
              else {
                if (a == "textgroupcompose") {
                  scope.tokenfunc("body_text", e, scope.mailA.body_text);
                }
                else {
                  if (a == "textgroupcomposetwoa") {
                    scope.tokenfunc("twomailabody_text", e, scope.mailA.body_text);
                  }
                  else {
                    if (a == "textgroupcomposetwob") {
                      scope.tokenfunc("twomailbbody_text", e, scope.mailB.body_text);
                    }
                    else {
                      if (a == "subgroupsuba") {
                        scope.tokenfunc("suba", e, scope.mailA.subject);
                      }
                      else {
                        if (a == "subgroupsubb") {
                          scope.tokenfunc("subb", e, scope.mailB.subject);
                        }
                        else {
                          if (a == "subgroupfrom") {
                            scope.tokenfunc("subfrom", e, scope.mailA.subject);
                          }
                          else {
                            if (a == "subgrouptwoa") {
                              scope.tokenfunc("twomaila", e, scope.mailA.subject);
                            }
                            else {
                              if (a == "subgrouptwob") {
                                scope.tokenfunc("twomailb", e, scope.mailB.subject);
                              }
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }


          scope.$apply();
          e.preventDefault();
        })
      }
    };
  });

  crmMailingAB.directive('stopa', function () {
    return {
      restrict: 'AE',
      link: function (scope, element, attrs) {
        scope.$watch('aastop', function () {
          if (scope.aastop == true) {
            $(element).dialog({
              title: 'Confirmation',
              width: 300,
              height: 150,
              closed: false,
              cache: false,
              modal: true,
              buttons: {
                'Yes': function () {
                  scope.aastop = false;
                  scope.copyatoc();
                  $(element).dialog("close");
                },
                'No': function () {
                  scope.aastop = false;
                  $(element).dialog("close");
                }
              },
              close: function () {
                scope.aastop = false;

                scope.$apply();
              }
            });
          }
        });
      }
    }
  });

  crmMailingAB.directive('stopb', function () {
    return {
      restrict: 'AE',
      link: function (scope, element, attrs) {

        scope.$watch('bbstop', function () {
          if (scope.bbstop == true) {
            $(element).dialog({
              title: 'Confirmation',
              width: 300,
              height: 150,
              closed: false,
              cache: false,
              modal: true,
              buttons: {
                'Yes': function () {
                  scope.bbstop = false;
                  scope.sendc();
                  $(element).dialog("close");
                },
                'No': function () {
                  scope.bbstop = false;
                  $(element).dialog("close");
                }
              },
              close: function () {
                scope.bbstop = false;

                scope.$apply();
              }
            });
          }
        });
      }
    }
  });


  crmMailingAB.directive('checktimeentry', function () {
    return {
      restrict: 'AE',
      link: function (scope, element, attrs) {
        $(element).timeEntry({show24Hours: true});
      }
    }
  });

  crmMailingAB.directive('ckedit', function ($parse) {
    CKEDITOR.disableAutoInline = true;
    var counter = 0,
      prefix = '__ckd_';

    return {
      restrict: 'A',
      link: function (scope, element, attrs, controller) {
        var getter = $parse(attrs.ckedit),
          setter = getter.assign;

        attrs.$set('contenteditable', true); // inline ckeditor needs this
        if (!attrs.id) {
          attrs.$set('id', prefix + (++counter));
        }

        // CKEditor stuff
        // Override the normal CKEditor save plugin

        CKEDITOR.plugins.registered['save'] =
        {
          init: function (editor) {
            editor.addCommand('save',
              {
                modes: { wysiwyg: 1, source: 1 },
                exec: function (editor) {
                  if (editor.checkDirty()) {
                    var ckValue = editor.getData();
                    scope.$apply(function () {
                      setter(scope, ckValue);
                    });
                    ckValue = null;
                    editor.resetDirty();
                  }
                }
              }
            );
            editor.ui.addButton('Save', { label: 'Save', command: 'save', toolbar: 'document' });
          }
        };
        var options = {};
        options.on = {
          blur: function (e) {
            if (e.editor.checkDirty()) {
              var ckValue = e.editor.getData();
              scope.$apply(function () {
                setter(scope, ckValue);
              });
              ckValue = null;
              e.editor.resetDirty();
            }
          }
        };
        options.extraPlugins = 'sourcedialog';
        options.removePlugins = 'sourcearea';
        var editorangular = CKEDITOR.inline(element[0], options); //invoke

        scope.$watch(attrs.ckedit, function (value) {
          editorangular.setData(value);
        });
      }
    }

  });

})(angular, CRM.$, CRM._);
