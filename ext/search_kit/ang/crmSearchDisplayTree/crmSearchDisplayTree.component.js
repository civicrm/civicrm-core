(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayTree').component('crmSearchDisplayTree', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      apiParams: '<',
      settings: '<',
      filters: '<',
      totalCount: '=?'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmSearchDisplayTree/crmSearchDisplayTree.html',
    controller: function($scope, $element, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplayEditableTrait) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      // Mix in required traits
      const ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplayTasksTrait), _.cloneDeep(searchDisplayEditableTrait));

      this.tree = [];

      this.$onInit = function() {
        this.onPostRun.push(function(apiResults, status) {
          if (status === 'success') {
            ctrl.tree = sortTree(apiResults.run);
          }
        });

        this.initializeDisplay($scope, $element);
      };

      // Capitalizing on the fact that javascript objects always copy-by-reference,
      // this creates a multi-level hierarchy in a single pass by placing children under their parents
      // while keeping a reference to children at the top level (which allows them to receive children of their own).
      function sortTree(results) {
        const parentField = ctrl.settings.parent_field;
        if (!parentField) {
          return results;
        }
        // Index by item.key
        const indexedResults = results.reduce((acc, item) => {
          item.children = [];
          acc[item.key] = item;
          return acc;
        }, {});
        // Place children into their parents
        results.forEach(item => {
          const parentKey = item.data[parentField];
          if (parentKey && indexedResults[parentKey]) {
            indexedResults[parentKey].children.push(item);
            // Now that the parent has children, set its 'collapsed' property.
            indexedResults[parentKey].collapsed = ctrl.settings.collapsible && ctrl.settings.collapsible === 'closed';
          }
        });
        // Remove children from the top level and what remains is a sorted tree
        return results.filter(item => !item.data[parentField]);
      }

    }
  });

})(angular, CRM.$, CRM._);
