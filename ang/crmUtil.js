/// crmUi: Sundry UI helpers
(function (angular, $, _) {
  angular.module('crmUtil', CRM.angRequires('crmUtil'));

  // Angular implementation of CRM.api3
  // @link https://docs.civicrm.org/dev/en/latest/api/interfaces/#angularjs
  //
  // Note: To mock API results in unit-tests, override crmApi.backend, e.g.
  //   var apiSpy = jasmine.createSpy('crmApi');
  //   crmApi.backend = apiSpy.and.returnValue(crmApi.val({
  //     is_error: 1
  //   }));
  angular.module('crmUtil').factory('crmApi', function($q) {
    var crmApi = function(entity, action, params, message) {
      // JSON serialization in CRM.api3 is not aware of Angular metadata like $$hash, so use angular.toJson()
      var deferred = $q.defer();
      var p;
      var backend = crmApi.backend || CRM.api3;
      if (params && params.body_html) {
        // CRM-18474 - remove Unicode Character 'LINE SEPARATOR' (U+2028)
        // and 'PARAGRAPH SEPARATOR' (U+2029) from the html if present.
        params.body_html = params.body_html.replace(/([\u2028]|[\u2029])/g, '\n');
      }
      if (_.isObject(entity)) {
        // eval content is locally generated.
        /*jshint -W061 */
        p = backend(eval('('+angular.toJson(entity)+')'), action);
      } else {
        // eval content is locally generated.
        /*jshint -W061 */
        p = backend(entity, action, eval('('+angular.toJson(params)+')'), message);
      }
      // CRM.api3 returns a promise, but the promise doesn't really represent errors as errors, so we
      // convert them
      p.then(
        function(result) {
          if (result.is_error) {
            deferred.reject(result);
          } else {
            deferred.resolve(result);
          }
        },
        function(error) {
          deferred.reject(error);
        }
      );
      return deferred.promise;
    };
    crmApi.backend = null;
    crmApi.val = function(value) {
      var d = $.Deferred();
      d.resolve(value);
      return d.promise();
    };
    return crmApi;
  });

  // Get and cache the metadata for an API entity.
  // usage:
  //   $q.when(crmMetadata.getFields('MyEntity'), function(fields){
  //     console.log('The fields are:', options);
  //   });
  angular.module('crmUtil').factory('crmMetadata', function($q, crmApi) {

    // Convert {key:$,value:$} sequence to unordered {$key: $value} map.
    function convertOptionsToMap(options) {
      var result = {};
      angular.forEach(options, function(o) {
        result[o.key] = o.value;
      });
      return result;
    }

    var cache = {}; // cache[entityName+'::'+action][fieldName].title
    var deferreds = {}; // deferreds[cacheKey].push($q.defer())
    var crmMetadata = {
      // usage: $q.when(crmMetadata.getField('MyEntity', 'my_field')).then(...);
      getField: function getField(entity, field) {
        return $q.when(crmMetadata.getFields(entity)).then(function(fields){
          return fields[field];
        });
      },
      // usage: $q.when(crmMetadata.getFields('MyEntity')).then(...);
      // usage: $q.when(crmMetadata.getFields(['MyEntity', 'myaction'])).then(...);
      getFields: function getFields(entity) {
        var action = '', cacheKey;
        if (_.isArray(entity)) {
          action = entity[1];
          entity = entity[0];
          cacheKey = entity + '::' + action;
        } else {
          cacheKey = entity;
        }

        if (_.isObject(cache[cacheKey])) {
          return cache[cacheKey];
        }

        var needFetch = _.isEmpty(deferreds[cacheKey]);
        deferreds[cacheKey] = deferreds[cacheKey] || [];
        var deferred = $q.defer();
        deferreds[cacheKey].push(deferred);

        if (needFetch) {
          crmApi(entity, 'getfields', {action: action, sequential: 1, options: {get_options: 'all'}})
            .then(
            // on success:
            function(fields) {
              cache[cacheKey] = _.indexBy(fields.values, 'name');
              angular.forEach(cache[cacheKey],function (field){
                if (field.options) {
                  field.optionsMap = convertOptionsToMap(field.options);
                }
              });
              angular.forEach(deferreds[cacheKey], function(dfr) {
                dfr.resolve(cache[cacheKey]);
              });
              delete deferreds[cacheKey];
            },
            // on error:
            function() {
              cache[cacheKey] = {}; // cache nack
              angular.forEach(deferreds[cacheKey], function(dfr) {
                dfr.reject();
              });
              delete deferreds[cacheKey];
            }
          );
        }

        return deferred.promise;
      }
    };

    return crmMetadata;
  });

  // usage:
  // var block = $scope.block = crmBlocker();
  // $scope.save = function() { return block(crmApi('MyEntity','create',...)); };
  // <button ng-click="save()" ng-disabled="block.check()">Do something</button>
  angular.module('crmUtil').factory('crmBlocker', function() {
    return function() {
      var blocks = 0;
      var result = function(promise) {
        blocks++;
        return promise.finally(function() {
          blocks--;
        });
      };
      result.check = function() {
        return blocks > 0;
      };
      return result;
    };
  });

  angular.module('crmUtil').factory('crmLegacy', function() {
    return CRM;
  });

  // example: scope.$watch('foo', crmLog.wrap(function(newValue, oldValue){ ... }));
  angular.module('crmUtil').factory('crmLog', function(){
    var level = 0;
    var write = console.log;
    function indent() {
      var s = '>';
      for (var i = 0; i < level; i++) s = s + '  ';
      return s;
    }
    var crmLog = {
      log: function(msg, vars) {
        write(indent() + msg, vars);
      },
      wrap: function(label, f) {
        return function(){
          level++;
          crmLog.log(label + ": start", arguments);
          var r;
          try {
            r = f.apply(this, arguments);
          } finally {
            crmLog.log(label + ": end");
            level--;
          }
          return r;
        };
      }
    };
    return crmLog;
  });

  angular.module('crmUtil').factory('crmNavigator', ['$window', function($window) {
    return {
      redirect: function(path) {
        $window.location.href = path;
      }
    };
  }]);

  // Wrap an async function in a queue, ensuring that independent async calls are issued in strict sequence.
  // usage: qApi = crmQueue(crmApi); qApi(entity,action,...).then(...); qApi(entity2,action2,...).then(...);
  // This is similar to promise-chaining, but allows chaining independent procs (without explicitly sharing promises).
  angular.module('crmUtil').factory('crmQueue', function($q) {
    // @param worker A function which generates promises
    return function crmQueue(worker) {
      var queue = [];
      function next() {
        var task = queue[0];
        worker.apply(null, task.a).then(
          function onOk(data) {
            queue.shift();
            task.dfr.resolve(data);
            if (queue.length > 0) next();
          },
          function onErr(err) {
            queue.shift();
            task.dfr.reject(err);
            if (queue.length > 0) next();
          }
        );
      }
      function enqueue() {
        var dfr = $q.defer();
        queue.push({a: arguments, dfr: dfr});
        if (queue.length === 1) {
          next();
        }
        return dfr.promise;
      }
      return enqueue;
    };
  });

  // Adapter for CRM.status which supports Angular promises (instead of jQuery promises)
  // example: crmStatus('Saving', crmApi(...)).then(function(result){...})
  angular.module('crmUtil').factory('crmStatus', function($q){
    return function(options, aPromise){
      if (aPromise) {
        return CRM.toAPromise($q, CRM.status(options, CRM.toJqPromise(aPromise)));
      } else {
        return CRM.toAPromise($q, CRM.status(options));
      }
    };
  });

  // crmWatcher allows one to setup event listeners and temporarily suspend
  // them en masse.
  //
  // example:
  // angular.controller(... function($scope, crmWatcher){
  //   var watcher = crmWatcher();
  //   function myfunc() {
  //     watcher.suspend('foo', function(){
  //       ...do stuff...
  //     });
  //   }
  //   watcher.setup('foo', function(){
  //     return [
  //       $scope.$watch('foo', myfunc),
  //       $scope.$watch('bar', myfunc),
  //       $scope.$watch('whiz', otherfunc)
  //     ];
  //   });
  // });
  angular.module('crmUtil').factory('crmWatcher', function(){
    return function() {
      var unwatches = {}, watchFactories = {}, suspends = {};

      // Specify the list of watches
      this.setup = function(name, newWatchFactory) {
        watchFactories[name] = newWatchFactory;
        unwatches[name] = watchFactories[name]();
        suspends[name] = 0;
        return this;
      };

      // Temporarily disable watches and run some logic
      this.suspend = function(name, f) {
        suspends[name]++;
        this.teardown(name);
        var r;
        try {
          r = f.apply(this, []);
        } finally {
          if (suspends[name] === 1) {
            unwatches[name] = watchFactories[name]();
            if (!angular.isArray(unwatches[name])) {
              unwatches[name] = [unwatches[name]];
            }
          }
          suspends[name]--;
        }
        return r;
      };

      this.teardown = function(name) {
        if (!unwatches[name]) return;
        _.each(unwatches[name], function(unwatch){
          unwatch();
        });
        delete unwatches[name];
      };

      return this;
    };
  });

  // Run a given function. If it is already running, wait for it to finish before running again.
  // If multiple requests are made before the first request finishes, all but the last will be ignored.
  // This prevents overwhelming the server with redundant queries during e.g. an autocomplete search while the user types.
  // Given function should return an angular promise. crmThrottle will deliver the contents when resolved.
  angular.module('crmUtil').factory('crmThrottle', function($q) {
    var pending = [],
      executing = [];
    return function(func) {
      var deferred = $q.defer();

      function checkResult(result, success) {
        _.pull(executing, func);
        if (_.includes(pending, func)) {
          runNext();
        } else if (success) {
          deferred.resolve(result);
        } else {
          deferred.reject(result);
        }
      }

      function runNext() {
        executing.push(func);
        _.pull(pending, func);
        func().then(function(result) {
          checkResult(result, true);
        }, function(result) {
          checkResult(result, false);
        });
      }

      if (!_.includes(executing, func)) {
        runNext();
      } else if (!_.includes(pending, func)) {
        pending.push(func);
      }
      return deferred.promise;
    };
  });

  angular.module('crmUtil').factory('crmLoadScript', function($q) {
    return function(url) {
      var deferred = $q.defer();

      CRM.loadScript(url).done(function() {
        deferred.resolve(true);
      });

      return deferred.promise;
    };
  });

  // usage: `Go to <a ng-href="#?name={{row.name|encodeURIComponent}}">page</a>`
  angular.module('crmUtil').filter('encodeURIComponent', function() {
    return function(input) {
      return input ? encodeURIComponent(input) : '';
    };
  });

})(angular, CRM.$, CRM._);
