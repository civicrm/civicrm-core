(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminJoin', {
    bindings: {
      apiEntity: '<',
      apiParams: '<',
      formValues: '<'
    },
    require: {
      crmSearchAdmin: '^',
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminJoin.html',
    controller: function ($scope, $element, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      $scope.controls = {joinType: 'LEFT'};

      this.joinTypes = [
        {k: 'LEFT', v: ts('With (optional)')},
        {k: 'INNER', v: ts('With (required)')},
        {k: 'EXCLUDE', v: ts('Without')},
      ];

      const searchInfo = {};

      this.$onInit = () => {
        searchInfo.api_entity = this.apiEntity;
        searchInfo.api_params = this.apiParams;
        searchInfo.form_values = this.formValues;
      };

      this.getJoin = (fullNameOrAlias) => {
        return searchMeta.getJoin(searchInfo, fullNameOrAlias);
      };

      const getExistingJoins = () => {
        return (this.apiParams.join || []).reduce((joins, join) => {
          joins[join[0].split(' AS ')[1]] = searchMeta.getJoin(searchInfo, join[0]);
          return joins;
        }, {});
      };

      const addNum = (name, num) => {
        return name + (num < 10 ? '_0' : '_') + num;
      };

      this.getJoinEntities = () => {
        const existingJoins = getExistingJoins();

        const addEntityJoins = (entity, stack, baseEntity) => {
          return Object.values(CRM.crmSearchAdmin.joins[entity] || {}).reduce((joinEntities, join) => {
            let num = 0;
            if (
              // Exclude joins that singly point back to the original entity
              !(baseEntity === join.entity && !join.multi) &&
              // Exclude joins to bridge tables
              !searchMeta.getEntity(join.entity).bridge
            ) {
              do {
                appendJoin(joinEntities, join, ++num, stack, entity);
              } while (addNum((stack ? stack + '_' : '') + join.alias, num) in existingJoins);
            }
            return joinEntities;
          }, []);
        };

        const appendJoin = (collection, join, num, stack, baseEntity) => {
          const alias = addNum((stack ? stack + '_' : '') + join.alias, num),
            opt = {
              id: join.entity + ' AS ' + alias,
              description: join.description,
              text: join.label + (num > 1 ? ' ' + num : ''),
              icon: searchMeta.getEntity(join.entity).icon,
              disabled: alias in existingJoins
            };
          if (alias in existingJoins) {
            opt.children = addEntityJoins(join.entity, alias, baseEntity);
          }
          collection.push(opt);
        };

        return {results: addEntityJoins(this.apiEntity)};
      };

      this.addJoin = (value) => {
        if (value) {
          this.apiParams.join = this.apiParams.join || [];
          const join = searchMeta.getJoin(searchInfo, value);
          const entity = searchMeta.getEntity(join.entity);
          const params = [value, $scope.controls.joinType || 'LEFT'];
          // Immutable conditions cannot be changed in the SK UI
          params.push(... _.cloneDeep(join.conditions || []));
          // Default conditions are user-editable in the SK UI
          params.push(... _.cloneDeep(join.defaults || []));
          this.apiParams.join.push(params);
          if (entity.search_fields && $scope.controls.joinType !== 'EXCLUDE') {
            // Add columns for newly-joined entity
            this.apiParams.select = this.apiParams.select || [];
            entity.search_fields.forEach((fieldName) => {
              // Try to avoid adding duplicate columns
              const simpleName = fieldName.split('.').at(-1);
              if (!this.apiParams.select.join(',').includes(simpleName)) {
                if (searchMeta.getField(fieldName, join.entity)) {
                  this.apiParams.select.push(join.alias + '.' + fieldName);
                }
              }
            });
          }
          this.crmSearchAdmin.loadFieldOptions();
        }
      };

      // Factory returns a getter-setter function for ngModel
      this.getSetJoinLabel = (joinName) => {
        return _.wrap(joinName, getSetJoinLabel);
      };

      const getSetJoinLabel = (...args) => {
        const joinName = args[0];
        const value = args[1];
        const joinInfo = searchMeta.getJoin(searchInfo, joinName);
        const alias = joinInfo.alias;
        // Setter
        if (args.length > 1) {
          if (this.formValues) {
            this.formValues.join = this.formValues.join || {};
            this.formValues.join[alias] = value;
            if (!value || value === joinInfo.defaultLabel) {
              delete this.formValues.join[alias];
            }
          }
        }
        return (this.formValues && this.formValues.join && this.formValues.join[alias]) || joinInfo.defaultLabel;
      };

      this.removeJoin = (index) => {
        const alias = searchMeta.getJoin(searchInfo, this.apiParams.join[index][0]).alias;
        this.apiParams.join.splice(index, 1);
        removeJoinStuff(alias);
      };

      const removeJoinStuff = (alias) => {
        // Process all arrays in reverse order to avoid index shifting
        if (this.apiParams.select) {
          Object.entries(this.apiParams.select).toReversed().forEach(([i, item]) => {
            if (item.startsWith(alias + '.')) {
              this.apiParams.select.splice(i, 1);
            }
          });
        }
        if (this.apiParams.where) {
          Object.entries(this.apiParams.where).toReversed().forEach(([i, clause]) => {
            if (clauseUsesJoin(clause, alias)) {
              this.apiParams.where.splice(i, 1);
            }
          });
        }
        if (this.apiParams.join) {
          Object.entries(this.apiParams.join).toReversed().forEach(([i, item]) => {
            const joinAlias = searchMeta.getJoin(searchInfo, item[0]).alias;
            if (joinAlias !== alias && joinAlias.indexOf(alias) === 0) {
              this.removeJoin(i);
            }
          });
        }
        if (this.formValues && this.formValues.join) {
          delete this.formValues.join[alias];
        }
      };

      this.changeJoinType = (join) => {
        if (join[1] === 'EXCLUDE') {
          removeJoinStuff(searchMeta.getJoin(searchInfo, join[0]).alias);
        }
      };

      const clauseUsesJoin = (clause, alias) => {
        if (clause[0].indexOf(alias + '.') === 0) {
          return true;
        }
        if (Array.isArray(clause[1])) {
          return clause[1].some((subClause) => {
            return clauseUsesJoin(subClause, alias);
          });
        }
        return false;
      };

      const fieldsForJoinGetters = {};

      const getFieldsForJoin = (joinEntity) => {
        return {results: this.crmSearchAdmin.getAllFields(searchInfo, ':name', ['Field', 'Custom', 'Extra'], null, joinEntity)};
      };

      this.fieldsForJoin = (joinEntity) => {
        if (!fieldsForJoinGetters[joinEntity]) {
          fieldsForJoinGetters[joinEntity] = _.wrap(joinEntity, getFieldsForJoin);
        }
        return fieldsForJoinGetters[joinEntity];
      };
    }
  });

})(angular, CRM.$, CRM._);
