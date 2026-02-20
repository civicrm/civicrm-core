// crmResource: Given a templateUrl "~/mymodule/myfile.html", load the matching HTML.
// This implementation loads all partials and strings in one batch.
(function(angular, $, _) {
  angular.module('crmResource', []);

  angular.module('crmResource').factory('crmResource', function($q, $http) {
    var deferreds = {}; // null|object; deferreds[url][idx] = Deferred;

    var notify = function notify() {
      var oldDfrds = deferreds;
      deferreds = null;

      angular.forEach(oldDfrds, function(dfrs, url) {
        if (CRM.angular.templates[url]) {
          angular.forEach(dfrs, function(dfr) {
            dfr.resolve({
              status: 200,
              headers: function(name) {
                var headers = {'Content-type': 'text/html'};
                return name ? headers[name] : headers;
              },
              data: CRM.angular.templates[url]
            });
          });
        }
        else {
          angular.forEach(dfrs, function(dfr) {
            dfr.reject({status: 500}); // FIXME
          });
        }
      });
    };

    var moduleUrl = CRM.angular.bundleUrl;
    $http.get(moduleUrl)
      .then(function httpSuccess(response) {
        CRM.angular.templates = CRM.angular.templates || {};
        angular.forEach(response.data, function (module) {
          if (module.partials) {
            angular.extend(CRM.angular.templates, module.partials);
          }
          if (module.strings) {
            CRM.addStrings(module.domain, module.strings);
          }
        });
        notify();
      }, function httpError() {
        notify();
      });

    return {
      // @return string|Promise<string>
      getUrl: function getUrl(url) {
        if (CRM.angular.templates && CRM.angular.templates[url]) {
          return CRM.angular.templates[url];
        }
        else {
          var deferred = $q.defer();
          if (!deferreds[url]) {
            deferreds[url] = [];
          }
          deferreds[url].push(deferred);
          return deferred.promise;
        }
      }
    };
  });

  const modulesPending = {};
  angular.module('crmResource').factory('crmResourceLoader', function ($q) {
    return {
      // Load Angular modules dynamically
      loadModules: function (moduleNames) {
        // Filter out modules that are already loaded.
        const loadedModules = CRM.angular.modules || [];
        const modulesToLoad = moduleNames.filter(name => !loadedModules.includes(name));

        // Early return if all modules are already loaded.
        if (modulesToLoad.length === 0) {
          return $q.resolve();
        }

        // Collect promises for modules that are already pending
        const pendingPromises = [];
        const newModulesToLoad = [];

        modulesToLoad.forEach(name => {
          if (modulesPending[name]) {
            pendingPromises.push(modulesPending[name]);
          } else {
            newModulesToLoad.push(name);
          }
        });

        // If all requested modules are pending, return combined promise
        if (newModulesToLoad.length === 0) {
          return $q.all(pendingPromises);
        }

        // Create a new promise for the modules being loaded
        const deferred = $q.defer();

        // Track all new modules with the same promise
        newModulesToLoad.forEach(name => {
          modulesPending[name] = deferred.promise;
        });

        const snippet = $('<div></div>');
        const settings = {
          url: CRM.url('civicrm/ajax/angular-modules', {modules: newModulesToLoad.join(',')}),
        };

        $(snippet).crmSnippet(settings)
          .on('crmLoad', function () {
            // Clean up pending tracking and resolve
            newModulesToLoad.forEach(name => delete modulesPending[name]);
            deferred.resolve();
          })
          .on('crmLoadFail', function () {
            // Clean up pending tracking and reject
            newModulesToLoad.forEach(name => delete modulesPending[name]);
            deferred.reject();
          })
          .crmSnippet('refresh');

        // Return combined promise of new and pending modules
        return $q.all([deferred.promise, ...pendingPromises]);
      }
    };
  });

  angular.module('crmResource').config(function($provide) {
    $provide.decorator('$templateCache', function($delegate, $http, $q, crmResource) {
      var origGet = $delegate.get;
      var urlPat = /^~\//;
      $delegate.get = function(url) {
        if (urlPat.test(url)) {
          return crmResource.getUrl(url);
        }
        else {
          return origGet.call(this, url);
        }
      };
      return $delegate;
    });
  });

})(angular, CRM.$, CRM._);
