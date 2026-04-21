(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminLinkGroup', {
    bindings: {
      group: '<',
      apiEntity: '<',
      apiParams: '<',
      links: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminLinkGroup.html',
    controller: function ($scope, $element, $timeout, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        linkProps = ['path', 'task', 'entity', 'action', 'join', 'target', 'icon', 'text', 'style', 'conditions'];

      this.styles = CRM.crmSearchAdmin.styles;

      this.getStyle = function(item) {
        return _.findWhere(this.styles, {key: item.style});
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

      };

    }
  });

})(angular, CRM.$, CRM._);
