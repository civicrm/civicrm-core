// crmResource: Given a templateUrl "~/mymodule/myfile.html", load the matching HTML.
// This implementation loads partials and strings in per-module batches.
// FIXME: handling of CRM.strings not well tested; may be racy
(function(angular, $, _) {
  angular.module('crmResource', []);

  angular.module('crmResource').factory('crmResource', function($q, $http) {
    var modules = {}; // moduleQueue[module] = 'loading'|Object;
    var templates = {}; // templates[url] = HTML;

    function CrmResourceModule(name) {
      this.name = name;
      this.status = 'new';  // loading|loaded|error
      this.data = null;
      this.deferreds = [];
    }

    angular.extend(CrmResourceModule.prototype, {
      createDeferred: function createDeferred() {
        var deferred = $q.defer();
        switch (this.status) {
          case 'new':
          case 'loading':
            this.deferreds.push(deferred);
            break;
          case 'loaded':
            deferred.resolve(this.data);
            break;
          case 'error':
            deferred.reject();
            break;
          default:
            throw 'Unknown status: ' + this.status;
        }
        return deferred.promise;
      },
      load: function load() {
        var module = this;
        this.status = 'loading';
        var moduleUrl = CRM.url('civicrm/ajax/angular-modules', {modules: module.name, l: CRM.config.lcMessages, r: CRM.angular.cacheCode});
        $http.get(moduleUrl)
          .success(function httpSuccess(data) {
            if (data[module.name]) {
              module.onSuccess(data[module.name]);
            }
            else {
              module.onError();
            }
          })
          .error(function httpError() {
            module.onError();
          });
      },
      onSuccess: function onSuccess(data) {
        var module = this;
        this.data = data;
        this.status = 'loaded';
        if (this.data.partials) {
          angular.extend(templates, this.data.partials);
        }
        if (this.data.strings) {
          CRM.addStrings(this.data.domain, this.data.strings);
        }
        angular.forEach(this.deferreds, function(deferred) {
          deferred.resolve(module.data);
        });
        delete this.deferreds;
      },
      onError: function onError() {
        this.status = 'error';
        angular.forEach(this.deferreds, function(deferred) {
          deferred.reject();
        });
        delete this.deferreds;
      }
    });

    return {
      // @return Promise<ModuleData>
      getModule: function getModule(name) {
        if (!modules[name]) {
          modules[name] = new CrmResourceModule(name);
          modules[name].load();
        }
        return modules[name].createDeferred();
      },
      // @return string|Promise<string>
      getUrl: function getUrl(url) {
        if (templates[url]) {
          return templates[url];
        }

        var parts = url.split('/');
        var deferred = $q.defer();
        this.getModule(parts[1]).then(
          function() {
            if (templates[url]) {
              deferred.resolve({
                status: 200,
                headers: function(name) {
                  var headers = {'Content-type': 'text/html'};
                  return name ? headers[name] : headers;
                },
                data: templates[url]
              });
            }
            else {
              deferred.reject({status: 500}); // FIXME
            }
          },
          function() {
            deferred.reject({status: 500}); // FIXME
          }
        );

        return deferred.promise;
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
