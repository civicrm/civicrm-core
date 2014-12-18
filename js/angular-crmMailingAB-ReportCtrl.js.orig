(function (angular, $, _) {

  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailingAB/' + relPath;
  };
  var crmMailingAB = angular.module('crmMailingAB');

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
      $location.path('abtest');
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
      $location.path('abtest');
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

})(angular, CRM.$, CRM._);
