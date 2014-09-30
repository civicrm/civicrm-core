/**
 * Created by aditya on 6/12/14.
 */
(function (angular, $, _) {

  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/abtesting/' + relPath;
  };
  var mltokens = [];
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

  crmMailingAB.controller('ReportCtrl', function ($scope, crmApi, selectedABTest, $location) {
    $scope.graph_data = [
      {},
      {},
      {},
      {},
      {}
    ];

    $scope.graphload = false;
    if (selectedABTest.winner_criteria_id == 1) {
      $scope.winnercriteria = "Open";
    }
    else {
      if (selectedABTest.winner_criteria_id == 2) {
        $scope.winnercriteria = "Total Unique Clicks";
      }
      else {
        if (selectedABTest.winner_criteria_id == 3) {
          $scope.winnercriteria = "Total Clicks on a particular link";
        }
      }
    }

    $scope.copyatoc = function () {
      var res = crmApi('Mailing', 'get', {id: selectedABTest.mailing_id_a});
      res.success(function (data) {
        for (var key in data.values) {
          var mail = data.values[key];
        }
        mail.id = selectedABTest.mailing_id_c;
        crmApi('Mailing', 'create', mail);
      });
      $location.path('mailing/abtesting');
    };

    $scope.sendc = function () {
      var res = crmApi('Mailing', 'get', {id: selectedABTest.mailing_id_b});
      res.success(function (data) {
        for (var key in data.values) {
          var mail = data.values[key];
        }
        mail.id = selectedABTest.mailing_id_c;
        crmApi('Mailing', 'create', mail);
      });
      $location.path('mailing/abtesting');
    };
    var result = crmApi('Mailing', 'stats', {mailing_id: selectedABTest.mailing_id_a});
    $scope.r = [];
    result.success(function (data) {
      $scope.rtt = data;
      $scope.r.push(data.values[selectedABTest.mailing_id_a]["Delivered"].toString());
      $scope.r.push(data.values[selectedABTest.mailing_id_a]["Bounces"].toString());
      $scope.r.push(data.values[selectedABTest.mailing_id_a]["Unsubscribers"].toString());
      $scope.r.push(data.values[selectedABTest.mailing_id_a]["Opened"].toString());
      $scope.r.push(data.values[selectedABTest.mailing_id_a]["Unique Clicks"].toString());
      $scope.$apply();
    });

    $scope.d = [];
    result = crmApi('Mailing', 'stats', {mailing_id: selectedABTest.mailing_id_b});
    result.success(function (data) {
      $scope.d.push(data.values[selectedABTest.mailing_id_b]["Delivered"].toString());
      $scope.d.push(data.values[selectedABTest.mailing_id_b]["Bounces"].toString());
      $scope.d.push(data.values[selectedABTest.mailing_id_b]["Unsubscribers"].toString());
      $scope.d.push(data.values[selectedABTest.mailing_id_b]["Opened"].toString());
      $scope.d.push(data.values[selectedABTest.mailing_id_b]["Unique Clicks"].toString());
      $scope.$apply();
    });
    $scope.aastop = false;
    $scope.asure = function () {
      $scope.aastop = true;
    };
    $scope.bbstop = false;
    $scope.bsure = function () {
      $scope.bbstop = true;
    };

    var numdiv = 5;
    var keep_cnt = 0;
    for (i = 1; i <= numdiv; i++) {
      var result = crmApi('MailingAB', 'graph_stats', {id: selectedABTest.id, split_count: numdiv, split_count_select: i});
      result.success(function (data) {
        var temp = 0;
        keep_cnt++;
        for (var key in data.values.A) {
          temp = key;
        }
        var t = data.values.A[temp].time.split(" ");
        var m = t[0];
        var year = t[2];
        var day = t[1].substr(0, t[1].length - 3);
        if (t[3] == "") {
          var t1 = t[4].split(":");
          var hur = t1[0];
          if (t[5] == "AM") {
            hour = hur;
            if (hour == 12) {
              hour = 0;
            }
          }
          if (t[5] == "PM") {
            hour = parseInt(hur) + 12;
          }
          var min = t1[1];
        }
        else {
          var t1 = t[3].split(":");
          var hur = t1[0];
          if (t[4] == "AM") {
            hour = hur;
            if (hour == 12) {
              hour = 0;
            }
          }
          if (t[4] == "PM") {
            hour = parseInt(hur) + 12;
          }
          var min = t1[1];
        }
        var month = 0;
        switch (m) {
          case "January":
            month = 0;
            break;
          case "February":
            month = 1;
            break;
          case "March":
            month = 2;
            break;
          case "April":
            month = 3;
            break;
          case "May":
            month = 4;
            break;
          case "June":
            month = 5;
            break;
          case "July":
            month = 6;
            break;
          case "August":
            month = 7;
            break;
          case "September":
            month = 8;
            break;
          case "October":
            month = 9;
            break;
          case "November":
            month = 10;
            break;
          case "December":
            month = 11;
            break;

        }
        var tp = new Date(year, month, day, hour, min, 0, 0);
        $scope.graph_data[temp - 1] = {
          time: tp,
          x: data.values.A[temp].count,
          y: data.values.B[temp].count
        };

        if (keep_cnt == numdiv) {
          $scope.graphload = true;
          $scope.$apply();
          var data = $scope.graph_data;

          // set up a colour variable
          var color = d3.scale.category10();

          // map one colour each to x, y and z
          // keys grabs the key value or heading of each key value pair in the json
          // but not time
          color.domain(d3.keys(data[0]).filter(function (key) {
            return key !== "time";
          }));

          // create a nested series for passing to the line generator
          // it's best understood by console logging the data
          var series = color.domain().map(function (name) {
            return {
              name: name,
              values: data.map(function (d) {
                return {
                  time: d.time,
                  score: +d[name]
                };
              })
            };
          });

          // Set the dimensions of the canvas / graph
          var margin = {
              top: 30,
              right: 20,
              bottom: 40,
              left: 75
            },
            width = 550 - margin.left - margin.right,
            height = 350 - margin.top - margin.bottom;

          // Set the ranges
          //var x = d3.time.scale().range([0, width]).domain([0,10]);
          var x = d3.time.scale().range([0, width]);
          var y = d3.scale.linear().range([height, 0]);

          // Define the axes
          var xAxis = d3.svg.axis().scale(x)
            .orient("bottom").ticks(10);

          var yAxis = d3.svg.axis().scale(y)
            .orient("left").ticks(5);

          // Define the line
          // Note you plot the time / score pair from each key you created ealier
          var valueline = d3.svg.line()
            .x(function (d) {
              return x(d.time);
            })
            .y(function (d) {
              return y(d.score);
            });

          // Adds the svg canvas
          var svg = d3.select("#linegraph")
            .append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g")
            .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

          // Scale the range of the data
          x.domain(d3.extent(data, function (d) {
            return d.time;
          }));

          // note the nested nature of this you need to dig an additional level
          y.domain([
            d3.min(series, function (c) {
              return d3.min(c.values, function (v) {
                return v.score;
              });
            }),
            d3.max(series, function (c) {
              return d3.max(c.values, function (v) {
                return v.score;
              });
            })
          ]);
          svg.append("text")      // text label for the x axis
            .attr("x", width / 2)
            .attr("y", height + margin.bottom)
            .style("text-anchor", "middle")
            .text("Time");

          svg.append("text")      // text label for the x axis
            .style("text-anchor", "middle")
            .text($scope.winnercriteria).attr("transform",function (d) {
              return "rotate(-90)"
            }).attr("x", -height / 2)
            .attr("y", -30);

          // create a variable called series and bind the date
          // for each series append a g element and class it as series for css styling
          var series = svg.selectAll(".series")
            .data(series)
            .enter().append("g")
            .attr("class", "series");

          // create the path for each series in the variable series i.e. x, y and z
          // pass each object called x, y nad z to the lne generator
          series.append("path")
            .attr("class", "line")
            .attr("d", function (d) {
              // console.log(d); // to see how d3 iterates through series
              return valueline(d.values);
            })
            .style("stroke", function (d) {
              return color(d.name);
            });

          // Add the X Axis
          svg.append("g") // Add the X Axis
            .attr("class", "x axis")
            .attr("transform", "translate(0," + height + ")")
            .call(xAxis)
            .selectAll("text")
            .attr("transform", function (d) {
              return "rotate(-30)";
            });

          // Add the Y Axis
          svg.append("g") // Add the Y Axis
            .attr("class", "y axis")
            .call(yAxis);
        }
      });
    }
    console.log($scope.graph_data);
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


    mltokens = CRM.crmMailing.mailTokens;
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
        $(element).select2({width: "200px", data: mltokens, placeholder: "Insert Token"});
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

