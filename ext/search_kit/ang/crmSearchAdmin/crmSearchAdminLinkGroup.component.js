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
        linkProps = ['path', 'task', 'entity', 'action', 'join', 'target', 'icon', 'text', 'style', 'condition'];

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

      this.onChangeCondition = function(item) {
        if (item.condition[0]) {
          item.condition[1] = '=';
        } else {
          item.condition = [];
        }
      };

      this.sortableOptions = {
        containment: $element,
        axis: 'y',
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
        searchMeta.pickIcon().then(function(icon) {
          ctrl.group[index].icon = icon;
        });
      };

      function setDefaults(item, newValue) {
        _.each(linkProps, function(prop) {
          item[prop] = newValue[prop] || (prop === 'condition' ? [] : '');
        });
      }

      this.addItem = function(item) {
        var newItem = _.pick(item, linkProps);
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
          condition: [],
          path: 'civicrm/'
        });
        var defaultLinks = _.filter(ctrl.links, function(link) {
          return link.action && !link.join;
        });
        _.each(ctrl.group, function(item) {
          setDefaults(item, item);
        });
        if (!ctrl.group.length) {
          if (defaultLinks.length) {
            _.each(defaultLinks, ctrl.addItem);
          } else {
            ctrl.addItem(JSON.parse(this.default));
          }
        }
        $element.on('change', 'select.crm-search-admin-add-link', function() {
          var $select = $(this);
          $scope.$apply(function() {
            ctrl.addItem(JSON.parse($select.val()));
            $select.val('');
          });
        });
      };

    }
  });

})(angular, CRM.$, CRM._);
