(function(angular, $, _) {
  // A modified version of ngIf to use afform.checkConditions
  angular.module('af').directive('afIf', function($compile, $animate, $parse) {
    return {
      multiElement: true,
      transclude: 'element',
      priority: 601,
      terminal: true,
      restrict: 'A',
      require: ['^^afForm'],
      $$tlb: true,
      link: function($scope, $element, $attr, ctrl, $transclude) {
        var block, childScope, previousElements;

        function watcher() {
          var conditions = $parse($attr.afIf)();
          return ctrl[0].checkConditions(conditions);
        }

        $scope.$watch(watcher, function(value) {
          if (value) {
            if (!childScope) {
              $transclude(function(clone, newScope) {
                childScope = newScope;
                clone[clone.length++] = $compile.$$createComment('end afIf', $attr.afIf);
                // Note: We only need the first/last node of the cloned nodes.
                // However, we need to keep the reference to the jqlite wrapper as it might be changed later
                // by a directive with templateUrl when its template arrives.
                block = {
                  clone: clone
                };
                $animate.enter(clone, $element.parent(), $element);
              });
            }
          } else {
            if (previousElements) {
              previousElements.remove();
              previousElements = null;
            }
            if (childScope) {
              // Alert afFields that they are about to be destroyed
              childScope.$broadcast('afIfDestroy');
              childScope.$destroy();
              childScope = null;
            }
            if (block) {
              previousElements = getBlockNodes(block.clone);
              $animate.leave(previousElements).done(function(response) {
                if (response !== false) previousElements = null;
              });
              block = null;
            }
          }
        });
      }
    };
  });

  /**
   * Return the DOM siblings between the first and last node in the given array.
   * @param {Array} array like object
   * @returns {Array} the inputted object or a jqLite collection containing the nodes
   */
  function getBlockNodes(nodes) {
    // TODO(perf): update `nodes` instead of creating a new object?
    var node = nodes[0];
    var endNode = nodes[nodes.length - 1];
    var blockNodes;

    for (var i = 1; node !== endNode && (node = node.nextSibling); i++) {
      if (blockNodes || nodes[i] !== node) {
        if (!blockNodes) {
          blockNodes = $(slice.call(nodes, 0, i));
        }
        blockNodes.push(node);
      }
    }

    return blockNodes || nodes;
  }

})(angular, CRM.$, CRM._);
