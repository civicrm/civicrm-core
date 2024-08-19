(function(angular, $, _) {
  // Example usage: <div af-join="Email" min="1" max="3" add-label="Add email" ><div join-email-default /></div>
  angular.module('af')
    .directive('afJoin', function() {
      return {
        restrict: 'A',
        require: ['afJoin', '^^afFieldset', '?^^afRepeatItem'],
        bindToController: {
          entity: '@afJoin',
        },
        link: function($scope, $el, $attr, ctrls) {
          var self = ctrls[0];
          self.afFieldset = ctrls[1];
          self.repeatItem = ctrls[2];
          // Used when there is > 1 block per entity
          self.offset = self.afFieldset.getJoinOffset($attr.afJoin);
        },
        controller: function($scope) {
          var self = this;
          this.getEntityType = function() {
            return this.entity;
          };
          this.getData = function() {
            var data, fieldsetData;
            if (self.repeatItem) {
              data = self.repeatItem.item;
            } else {
              fieldsetData = self.afFieldset.getData();
              if (!fieldsetData.length) {
                fieldsetData.push({fields: {}, joins: {}});
              }
              data = fieldsetData[0];
            }
            if (!data.joins) {
              data.joins = {};
            }
            if (!data.joins[self.entity]) {
              data.joins[self.entity] = [];
            }
            return data.joins[self.entity];
          };
          this.getFieldData = function() {
            var data = this.getData();
            if (!data[this.offset]) {
              data[this.offset] = {};
            }
            return data[this.offset];
          };
        }
      };
    });
})(angular, CRM.$, CRM._);
