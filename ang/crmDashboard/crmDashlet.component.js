(function(angular, $, _) {

  angular.module('crmDashboard').component('crmDashlet', {
    bindings: {
      dashlet: '<',
      remove: '&',
      fullscreen: '&',
      isFullscreen: '<'
    },
    templateUrl: '~/crmDashboard/Dashlet.html',
    controller: function ($scope, $element, $timeout, $interval) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this,
        lastLoaded,
        checker;

      function getCache() {
        return CRM.cache.get('dashboard', {})[ctrl.dashlet.id] || {};
      }

      function setCache(content) {
        var data = CRM.cache.get('dashboard', {}),
          cached = data[ctrl.dashlet.id] || {};
        data[ctrl.dashlet.id] = {
          content: content || cached.content || null,
          collapsed: ctrl.collapsed,
          lastLoaded: content ? $.now() : (cached.lastLoaded || null)
        };
        CRM.cache.set('dashboard', data);
        lastLoaded = data[ctrl.dashlet.id].lastLoaded;
      }

      function isFresh() {
        return lastLoaded && (ctrl.dashlet.cache_minutes * 60000 + lastLoaded) > $.now();
      }

      function setChecker() {
        if (angular.isUndefined(checker)) {
          checker = $interval(function() {
            if (!ctrl.collapsed && !isFresh() && (!document.hasFocus || document.hasFocus())) {
              stopChecker();
              reload(ctrl.dashlet.url);
            }
          }, 1000);
        }
      }

      function stopChecker() {
        if (angular.isDefined(checker)) {
          $interval.cancel(checker);
          checker = undefined;
        }
      }

      this.toggleCollapse = function() {
        ctrl.collapsed = !ctrl.collapsed;
        setCache();
      };

      this.forceRefresh = function() {
        if (ctrl.dashlet.url) {
          reload(ctrl.dashlet.url);
        } else if (ctrl.dashlet.directive) {
          var directive = ctrl.dashlet.directive;
          ctrl.dashlet.directive = null;
          $timeout(function() {
            ctrl.dashlet.directive = directive;
          }, 10);
        }
      };

      function reload(path)  {
        var extern = path.slice(0, 1) === '/' || path.slice(0, 4) === 'http',
          url = extern ? path : CRM.url(path);
        CRM.loadPage(url, {target: $('.crm-dashlet-content', $element)});
      }

      this.$onInit = function() {
        if (this.isFullscreen && this.dashlet.fullscreen_url) {
          reload(this.dashlet.fullscreen_url);
          return;
        }

        var cache = getCache();
        lastLoaded = cache.lastLoaded;
        ctrl.collapsed = !this.fullscreen && !!cache.collapsed;

        if (ctrl.dashlet.url) {
          var fresh = cache.content && isFresh();
          if (fresh) {
            $('.crm-dashlet-content', $element).html(cache.content).trigger('crmLoad');
            setChecker();
          }

          $element.on('crmLoad', function(event, data) {
            if ($(event.target).is('.crm-dashlet-content')) {
              setCache(data.content);
              setChecker();
            }
          });

          if (!fresh) {
            reload(ctrl.dashlet.url);
          }
        }

      };

      this.$onDestroy = function() {
        stopChecker();
      };
    }
  });

})(angular, CRM.$, CRM._);
