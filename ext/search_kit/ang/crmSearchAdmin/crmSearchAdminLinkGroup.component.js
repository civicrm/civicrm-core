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
        ctrl = this;

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

      this.addItem = function(path) {
        var link = ctrl.getLink(path);
        ctrl.group.push({
          path: path,
          style: link && link.style || 'default',
          text: link ? link.title : ts('Link'),
          icon: link && link.icon || 'fa-external-link'
        });
      };

      this.onChangeLink = function(item, before, after) {
        var beforeLink = before && ctrl.getLink(before),
          beforeTitle = beforeLink ? beforeLink.title : ts('Link'),
          afterLink = after && ctrl.getLink(after);
        if (afterLink && (!item.text || beforeTitle === item.text)) {
          item.text = afterLink.title;
        }
      };

      this.$onInit = function() {
        var defaultLinks = _.filter(ctrl.links, function(link) {
          return !link.join;
        });
        if (!ctrl.group.length) {
          if (defaultLinks.length) {
            _.each(_.pluck(defaultLinks, 'path'), ctrl.addItem);
          } else {
            ctrl.addItem('civicrm/');
          }
        }
        $element.on('change', 'select.crm-search-admin-add-link', function() {
          var $select = $(this);
          $scope.$apply(function() {
            ctrl.addItem($select.val());
            $select.val('');
          });
        });
      };

      this.getLink = function(path) {
        return _.findWhere(ctrl.links, {path: path});
      };

    }
  });

})(angular, CRM.$, CRM._);
