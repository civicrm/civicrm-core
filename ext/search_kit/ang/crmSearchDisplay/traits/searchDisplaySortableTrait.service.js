(function(angular, $, _) {
  "use strict";

  // Trait shared by any search display controllers which allow sorting
  angular.module('crmSearchDisplay').factory('searchDisplaySortableTrait', function() {
    var ts = CRM.ts('org.civicrm.search_kit');

    // Trait properties get mixed into display controller using angular.extend()
    return {

      isSortable: function(col) {
        return !this.settings.draggable && col.type === 'field' && col.sortable !== false;
      },

      getSort: function(col) {
        var dir = _.reduce(this.sort, function(dir, item) {
          return item[0] === col.key ? item[1] : dir;
        }, null);
        if (dir) {
          return 'fa-sort-' + dir.toLowerCase();
        }
        return 'fa-sort disabled';
      },

      setSort: function(col, $event) {
        if (!this.isSortable(col)) {
          return;
        }
        var dir = this.getSort(col) === 'fa-sort-asc' ? 'DESC' : 'ASC';
        if (!$event.shiftKey || !this.sort) {
          this.sort = [];
        }
        var index = _.findIndex(this.sort, [col.key]);
        if (index > -1) {
          this.sort[index][1] = dir;
        } else {
          this.sort.push([col.key, dir]);
        }
        if (this.results || !this.settings.button) {
          this.getResultsPronto();
        }
      }

    };
  });

})(angular, CRM.$, CRM._);
