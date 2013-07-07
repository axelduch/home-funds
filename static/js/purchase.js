/**
 * Created with JetBrains WebStorm.
 * User: AKA
 * Date: 29/05/13
 * Time: 11:29
 * To change this template use File | Settings | File Templates.
 */
'use strict';
var app = angular.module('purchase', ['rest']),
    directivesPath = GLOBAL_APP.root + '/static/html/directives/';

app.config(function ($routeProvider) {
    $routeProvider
        .when('/', {controller: PurchaseListCtrl, templateUrl: directivesPath + 'list.html',
            resolve: {
                purchasesResponse: function (purchaseService) {
                    return purchaseService.query();
                }
            }
        })
        .when('/sub/:sub/:purchaseId', {controller: PurchaseListCtrl, templateUrl: directivesPath + 'list.html',
            resolve: {
                purchasesResponse: function (purchaseService) {
                    return purchaseService.query();
                }
            }
        })
        .when('/sub/:sub', {controller: PurchaseListCtrl, templateUrl: directivesPath + 'list.html',
            resolve: {
                purchasesResponse: function (purchaseService) {
                    return purchaseService.query();
                }
            }
        })
        .otherwise({redirectTo: '/'});
});

function PurchaseListCtrl($scope, $routeParams, purchaseService, purchasesResponse) {
    console.log('ListCtrl');
    $scope.sub = $routeParams.sub;
    $scope.purchases = purchasesResponse.data;
    $scope.purchases.map(function (purchase) {
        // format date
        var date = new Date(purchase.date);
        purchase.date = date.toLocaleDateString('fr-FR');
    });
    $scope.budget = purchaseService.calculateBudget();
    $scope.moneySpent = purchaseService.getMoneySpent();
    $scope.average = $scope.purchases.length ? ($scope.moneySpent / $scope.purchases.length) : 0;
    console.log('/ListCtrl');
}

function PurchaseCreateCtrl ($scope, $location, $routeParams, purchaseService) {
    console.log('CreateCtrl');
    $scope.sub = $routeParams.sub;
    $scope.purchase = {
        name: '',
        description: '',
        price: 0
    }

    $scope.save = function() {
        console.log(purchaseService);
        purchaseService.save($scope.purchase, function(purchase) {
            $location.path('/');
        });
    };
    console.log('/CreateCtrl');
}

function PurchaseEditCtrl ($scope, $location, $routeParams, purchaseService) {
    if ($routeParams.purchaseId && !$routeParams.purchaseId.match(/^\d+$/)) {
        throw 'Error: $routeParams.purchaseId should be an integer >= zero';
    }
    $scope.switchEdit = 'false';

    if ($routeParams.purchaseId === $scope.purchase.id) {
        console.log('ok !');
        $scope.switchEdit = 'true';
    }
    $scope.edit = function () {
        $location.path('/sub/edit/' + $scope.purchase.id);
    };

    $scope.delete = function () {
        purchaseService.delete($scope.purchase.id, function (resp) {
            console.log($scope.purchase.id);
            for (var i = 0, l = $scope.purchases.length; i < l; i++) {
                if ($scope.purchases[i].id === $scope.purchase.id) {
                    $location.path('/');
                    break;
                }
            }
        });
    }
}