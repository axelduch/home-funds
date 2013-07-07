angular.module('purchase')
.directive('subNav', function () {
    return {
        restrict: 'E',
        scope: {
            current: '=current'
        },
        templateUrl: directivesPath + 'sub-nav.html',
        controller: function ($scope) {
        }
    };
})
.directive('new', function () {
    return {
        restrict: 'E',
        templateUrl: directivesPath + 'create.html',
        controller: function ($scope) {

        }
    }
});