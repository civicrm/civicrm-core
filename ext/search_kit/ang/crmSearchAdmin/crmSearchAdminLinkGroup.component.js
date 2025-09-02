(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminLinkGroup', {
    bindings: {
      group: '<',
      apiEntity: '<',
      apiParams: '<',
      links: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminLinkGroup.html',
    controller: function ($scope, $element, $timeout, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        linkProps = ['path', 'task', 'entity', 'action', 'join', 'target', 'icon', 'text', 'style', 'conditions'];

      this.conditionCount = [];

      ctrl.permissionOperators = [
        {key: 'CONTAINS', value: ts('Includes')},
        {key: '=', value: ts('Has All')},
        {key: '!=', value: ts('Lacks All')}
      ];

      this.styles = CRM.crmSearchAdmin.styles;

      this.getStyle = function(item) {
        return _.findWhere(this.styles, {key: item.style});
      };

      this.getField = searchMeta.getField;

      this.fields = function() {
        let selectFields = ctrl.crmSearchAdmin.getSelectFields();
        // Use machine names not labels for option matching
        selectFields.forEach((field) => field.id = field.id.replace(':label', ':name'));
        let permissionField = [{
          text: ts('Current User Permission'),
          id: 'check user permission',
          description: ts('Check permission of logged-in user')
        }];
        return {results: permissionField.concat(selectFields)};
      };

      this.addCondition = function(item, selection) {
        item.conditions.push([selection, '=']);
      };

      this.onChangeCondition = function(item, index) {
        if (item.conditions[index][0]) {
          item.conditions[index][1] = '=';
        } else {
          item.conditions.splice(index, 1);
        }
      };

      this.sortableOptions = {
        containment: $element.children('table').first(),
        helper: function(e, ui) {
          // Prevent table row width from changing during drag
          ui.children().each(function() {
            $(this).width($(this).width());
          });
          return ui;
        }
      };

      this.permissions = CRM.crmSearchAdmin.permissions;

      $scope.pickIcon = function(index) {
        searchMeta.pickIcon().then(icon => ctrl.group[index].icon = icon);
      };

      function setDefaults(item, newValue) {
        // Backward support for singular "condition" from older versions of SearchKit (pre 6.4)
        if (newValue.condition && newValue.condition.length && (!item.conditions|| !item.conditions.length)) {
          item.conditions = [newValue.condition];
          delete item.condition;
        }
        linkProps.forEach(prop => {
          item[prop] = newValue[prop] || (prop === 'conditions' ? [] : '');
        });
      }

      this.addItem = function(item) {
        const newItem = _.pick(item, linkProps);
        setDefaults(newItem, newItem);
        ctrl.group.push(newItem);
      };

      this.onChangeLink = function(item, newValue) {
        if (newValue.path === 'civicrm/') {
          newValue = JSON.parse(this.default);
        }
        setDefaults(item, newValue);
      };

      this.serialize = JSON.stringify;

      this.$onInit = function() {
        this.default = this.serialize({
          style: 'default',
          text: ts('Link'),
          icon: 'fa-external-link',
          conditions: [],
          path: 'civicrm/'
        });
        if (!ctrl.group.length) {
          const defaultLinks = ctrl.links.filter(link => link.action && !link.join);
          if (defaultLinks.length) {
            defaultLinks.forEach(ctrl.addItem);
          } else {
            ctrl.addItem(JSON.parse(this.default));
          }
        }
        else {
          ctrl.group.forEach(item => setDefaults(item, item));
        }
        $element.on('change', 'select.crm-search-admin-add-link', function() {
          const $select = $(this);
          $scope.$apply(function() {
            ctrl.addItem(JSON.parse($select.val()));
            $select.val('');
          });
        });

        // Track number of conditions per item, for use with the "Add Condition" selector
        $scope.$watch('$ctrl.group', function() {
          // Timeout prevents bouncy-ness in the onChange of the "Add Condition" element
          $timeout(function() {
            ctrl.conditionCount = _.map(ctrl.group, item => item.conditions.length);
          });
        }, true);

      };

    }
  });

})(angular, CRM.$, CRM._);
