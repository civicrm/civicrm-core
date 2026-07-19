(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminEntitySetSelect', {
    bindings: {
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminEntitySetSelect.html',
    controller: function ($scope, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      $scope.getEntity = searchMeta.getEntity;
      this.setOperations = CRM.crmSearchAdmin.setOperations;
      this.addEntitySelect = searchMeta.getPrimaryAndSecondaryEntitySelect((entity) => entity.params.includes('groupBy'));

      this.hasFunction = (expr) => {
        return expr && this.crmSearchAdmin.hasFunction(expr);
      };

      this.isGroupBySupported = (entityName) => {
        return searchMeta.getEntity(entityName)?.params?.includes('groupBy');
      };

      this.fieldsForEntitySet = (setIndex) => {
        return () => {
          const set = this.apiParams.sets[setIndex];
          const setSearch = {
            api_entity: set[1],
            api_params: set[3],
          };
          return {
            results: this.crmSearchAdmin.getAllFields(setSearch, ':label', ['Field', 'Custom', 'Extra'], (key) => set[3].select.includes(key))
          };
        };
      };

      const ifExists = (apiEntity, apiParams, selectExpr) => {
        const info = searchMeta.parseExpr(selectExpr, {api_entity: apiEntity, api_params: apiParams});
        return (info?.args?.find(arg => arg.type === 'field')) ? selectExpr : null;
      };

      this.addEntitySet = (newEntity) => {
        // Construct default select for the new set based on the first set
        const firstSet = this.apiParams.sets?.[0];
        const setOp = firstSet?.[0] || 'UNION ALL';
        const firstSelect = firstSet?.[3]?.select || [];

        const newSelect = firstSelect.map((selectExpr) => {
          // If this field expr is valid on the new entity, add it
         return ifExists(newEntity, {}, selectExpr);
        });

        this.apiParams.sets.push([setOp, newEntity, 'get', {select: newSelect, where: []}]);
      };

      this.addEntitySetRow = (fieldName) => {
        const sets = this.apiParams.sets;
        this.apiParams.select.push(fieldName);
        sets.forEach((set, i) => {
          set[3].select = set[3].select || [];
          set[3].select.push(ifExists(set[1], set[3], fieldName));
        });
      };

      this.removeEntitySetRow = (index) => {
        const firstSet = this.apiParams.sets[0];
        const exprFromFirstSet = firstSet[3].select[index];
        const infoFromFirstSet = searchMeta.parseExpr(exprFromFirstSet, {api_entity: firstSet[1], api_params: firstSet[3]});
        // Remove that index from every set
        this.apiParams.sets.forEach((set) => {
          set[3].select.splice(index, 1);
        });
        // Remove matching fields from the main select
        const targetFieldName = infoFromFirstSet?.alias?.split(':')[0];
        if (targetFieldName && this.apiParams.select) {
          Object.entries(this.apiParams.select).toReversed().forEach(([i, col]) => {
            const colInfo = searchMeta.parseExpr(col, {api_entity: this.apiEntity, api_params: this.apiParams});
            const matches = colInfo?.args?.some(arg => arg.type === 'field' && (arg.path === targetFieldName || arg.field?.name === targetFieldName));
            if (matches) {
              this.apiParams.select.splice(i, 1);
            }
          });
        }
      };

      this.removeEntitySet = (index) => {
        this.apiParams.sets.splice(index, 1);
        // When deleting the first set, re-sync the main select with the new first set's fields
        if (index === 0 && this.apiParams.sets.length) {
          // Find indices of nulls in the new first set's select, then remove them from all sets
          const nullIndices = (this.apiParams.sets[0][3].select || []).reduce((acc, field, i) => {
            if (field === null) { acc.push(i); }
            return acc;
          }, []);
          nullIndices.toReversed().forEach((nullIndex) => {
            this.apiParams.sets.forEach((set) => {
              set[3].select.splice(nullIndex, 1);
            });
          });
          this.apiParams.select = this.apiParams.sets[0][3].select.map((field) => _.last(field.split(' AS ')));
        }
      };

      // When updating a field in the first set, also update the main select
      $scope.$watch(() => this.apiParams.sets?.[0]?.[3]?.select, (newSelect, oldSelect) => {
        if (!newSelect || !oldSelect || newSelect === oldSelect) {
          return;
        }
        newSelect.forEach((newExpr, index) => {
          const oldExpr = oldSelect[index];
          if (oldExpr && newExpr !== oldExpr) {
            const firstSet = this.apiParams.sets[0];
            const oldInfo = searchMeta.parseExpr(oldExpr, {api_entity: firstSet[1], api_params: firstSet[3]});
            const newInfo = searchMeta.parseExpr(newExpr, {api_entity: firstSet[1], api_params: firstSet[3]});
            const oldAlias = oldInfo?.alias;
            const newAlias = newInfo?.alias;
            if (oldAlias && newAlias && oldAlias !== newAlias && this.apiParams.select) {
              const selectIndex = this.apiParams.select.indexOf(oldAlias);
              if (selectIndex > -1) {
                this.apiParams.select[selectIndex] = newAlias;
              }
            }
          }
        });
      }, true);

    }
  });

})(angular, CRM.$, CRM._);
