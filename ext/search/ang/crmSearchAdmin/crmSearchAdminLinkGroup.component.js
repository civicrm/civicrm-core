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
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      this.styles = CRM.crmSearchAdmin.styles;

      this.setValue = function(val, index) {
        var link = ctrl.getLink(val),
          item = ctrl.group[index];
        if (item.path === val) {
          return;
        }
        item.path = val;
        item.icon = link ? defaultIcons[link.action] : 'fa-external-link';
        if (val === 'civicrm/') {
          $timeout(function () {
            $('tr:eq(' + index + ') input[type=text]', $element).focus();
          });
        }
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

      var defaultIcons = {
        view: 'fa-external-link',
        update: 'fa-pencil',
        delete: 'fa-trash'
      };

      var defaultStyles = {
        view: 'primary',
        update: 'warning',
        delete: 'danger'
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
          style: link && defaultStyles[link.action] || 'default',
          text: link ? link.title : '',
          icon: link && defaultIcons[link.action] || 'fa-external-link'
        });
      };

      this.$onInit = function() {
        if (!ctrl.group.length) {
          if (ctrl.links.length) {
            _.each(_.pluck(ctrl.links, 'path'), ctrl.addItem);
          } else {
            ctrl.addItem('civicrm/');
          }
        }
        $element.on('change', 'select.crm-search-admin-select-path', function() {
          var $select = $(this);
          $scope.$apply(function() {
            if ($select.closest('tfoot').length) {
              ctrl.addItem($select.val());
              $select.val('');
            } else {
              ctrl.setValue($select.val(), $select.closest('tr').index());
            }
          });
        });
      };

      this.getLink = function(path) {
        return _.findWhere(ctrl.links, {path: path});
      };

    }
  });

})(angular, CRM.$, CRM._);
