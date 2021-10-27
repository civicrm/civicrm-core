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
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        linkProps = ['path', 'entity', 'action', 'join', 'target', 'icon', 'text', 'style'];

      this.styles = CRM.crmSearchAdmin.styles;

      this.getStyle = function(item) {
        return _.findWhere(this.styles, {key: item.style});
      };

      this.sortableOptions = {
        containment: 'tbody',
        direction: 'vertical',
        helper: function(e, ui) {
          // Prevent table row width from changing during drag
          ui.children().each(function() {
            $(this).width($(this).width());
          });
          return ui;
        }
      };

      $scope.pickIcon = function(index) {
        searchMeta.pickIcon().then(function(icon) {
          ctrl.group[index].icon = icon;
        });
      };

      this.addItem = function(item) {
        ctrl.group.push(_.pick(item, linkProps));
      };

      this.onChangeLink = function(item, newValue) {
        if (newValue.path === 'civicrm/') {
          newValue = JSON.parse(this.default);
        }
        _.each(linkProps, function(prop) {
          item[prop] = newValue[prop] || '';
        });
      };

      this.serialize = JSON.stringify;

      this.$onInit = function() {
        this.default = this.serialize({
          style: 'default',
          text: ts('Link'),
          icon: 'fa-external-link',
          path: 'civicrm/'
        });
        var defaultLinks = _.filter(ctrl.links, function(link) {
          return !link.join;
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
