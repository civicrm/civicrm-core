(function (angular, $, _) {


  // FIXME: This code is long and hasn't been fully working for me, but I've moved it into a spot
  // where it at least fits in a bit better.

  // example: <div crm-mailing-ab-stats="{split_count: 6, criteria:'Open'}" crm-abtest="myabtest" />
  // options (see also: Mailing.graph_stats API)
  //  - split_count: int
  //  - criteria: string
  //  - target_date: string, date
  //  - target_url: string
  angular.module('crmMailingAB').directive('crmMailingAbStats', function (crmApi, $parse) {
    return {
      scope: {
        crmMailingAbStats: '@',
        crmAbtest: '@'
      },
      template: '<div class="crm-mailing-ab-stats"></div>',
      link: function (scope, element, attrs) {
        var abtestModel = $parse(attrs.crmAbtest);
        var optionModel = $parse(attrs.crmMailingAbStats);
        var options = angular.extend({}, optionModel(scope.$parent), {
          criteria: 'Open', // e.g. 'Open', 'Total Unique Clicks'
          split_count: 5
        });

        scope.$watch(attrs.crmAbtest, refresh);
        function refresh() {
          var abtest = abtestModel(scope.$parent);
          if (!abtest) {
            console.log('failed to draw stats - missing abtest');
            return;
          }

          scope.graph_data = [
            {},
            {},
            {},
            {},
            {}
          ];
          var keep_cnt = 0;

          for (var i = 1; i <= options.split_count; i++) {
            var result = crmApi('MailingAB', 'graph_stats', {
              id: abtest.ab.id,
              target_date: abtest.ab.declare_winning_time ? abtest.ab.declare_winning_time : 'now',
              target_url: null, // FIXME
              criteria: options.criteria,
              split_count: options.split_count,
              split_count_select: i
            });
            /*jshint -W083 */
            result.then(function (data) {
              var temp = 0;
              keep_cnt++;
              for (var key in data.values.A) {
                temp = key;
              }
              var t = data.values.A[temp].time.split(" ");
              var m = t[0];
              var year = t[2];
              var day = t[1].substr(0, t[1].length - 3);
              var t1, hur, hour, min;
              if (_.isEmpty(t[3])) {
                t1 = t[4].split(":");
                hur = t1[0];
                if (t[5] == "AM") {
                  hour = hur;
                  if (hour == 12) {
                    hour = 0;
                  }
                }
                if (t[5] == "PM") {
                  hour = parseInt(hur) + 12;
                }
                min = t1[1];
              }
              else {
                t1 = t[3].split(":");
                hur = t1[0];
                if (t[4] == "AM") {
                  hour = hur;
                  if (hour == 12) {
                    hour = 0;
                  }
                }
                if (t[4] == "PM") {
                  hour = parseInt(hur) + 12;
                }
                min = t1[1];
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
              scope.graph_data[temp - 1] = {
                time: tp,
                x: data.values.A[temp].count,
                y: data.values.B[temp].count
              };

              if (keep_cnt == options.split_count) {
                scope.graphload = true;
                data = scope.graph_data;

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
                // Note you plot the time / score pair from each key you created earlier
                var valueline = d3.svg.line()
                  .x(function (d) {
                    return x(d.time);
                  })
                  .y(function (d) {
                    return y(d.score);
                  });

                // Adds the svg canvas
                var svg = d3.select($('.crm-mailing-ab-stats', element)[0])
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
                  .text(scope.winnercriteria).attr("transform",function (d) {
                    return "rotate(-90)";
                  }).attr("x", -height / 2)
                  .attr("y", -30);

                // create a variable called series and bind the date
                // for each series append a g element and class it as series for css styling
                series = svg.selectAll(".series")
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
        }
      } // link()
    };
  });
})(angular, CRM.$, CRM._);
