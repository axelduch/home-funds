/**
 * Created with JetBrains WebStorm.
 * User: AKA
 * Date: 30/05/13
 * Time: 18:09
 * To change this template use File | Settings | File Templates.
 */
angular.module('rest', ['ngResource'])
   .service('purchaseService', function localStorage ($resource, $http) {
        'use strict';
        console.log('factory');
        localStorage.cache = localStorage.cache || [];

        var resource = $resource(GLOBAL_APP.apiUrl + '/purchase/:id', {}, {}),
            cache = localStorage.cache,
            budget = 440.83,
            moneySpent = 0;

        this.getMoneySpent = function () {
            return moneySpent;
        };
        this.calculateBudget = function () {
            return budget - moneySpent;
        };
        this.allTimeBudget = function () {
            return budget
        };

        this.get = function (action, id) {
            console.log('purchaseService.get');
            var result;
            for (var i = 0, l = cache.length; i < l; i++) {
                if (cache[i].id === id) {
                    console.log('returning from cache id ' + id);
                    result = cache[i];
                }
            }
            if (result === undefined) {
                resource.get(id,
                    function (data) {
                        result = angular.copy(data);
                        setCached([angular.copy(result)]);
                    },
                    function (error) {
                        // TODO
                    }
                );
            }
            console.log('/purchaseService.get');
            return result;
        }

        this.query = function () {
            return $http({
                method: 'GET',
                url: GLOBAL_APP.apiUrl + '/purchase',
                cache: false
            })
            .success(function (data) {
                moneySpent = 0;
                data.map(function (purchase) {
                   moneySpent += parseFloat(purchase.price);
                });
                setCached(data);
            })
            .error(function (error) {
                // TODO
            });
        };

        this.delete = function (id, fnCallback) {
            $http({
                method: 'DELETE',
                url: GLOBAL_APP.apiUrl + '/purchase/' + id
            })
            .success(function (resp) {
                fnCallback(resp);
                uncache();
            })
            .error(function(e) {
                console.log('error');
                console.error(e);
            });
        };
        this.save = function (data, fnCallback) {
            resource.save(data, function(purchase) {
                fnCallback(purchase);
            });
        };
        /**
         *
         * @param {Array} objects
         * @returns {boolean}
         */
        function setCached(objects) {
            if (!angular.isArray(objects)) {
                throw 'Protected method purchaseService::cache expects parameter to be an Array';
            }
            for (var i = 0, l = objects.length; i < l; i++) {
                if (cache.indexOf(objects[i]) === -1) {
                   cache.push(objects[i]);
                }
            }
            return true;
        }
        /**
         *
         * @param {integer} objects
         * @returns {boolean}
         */
        function uncache() {
            cache = localStorage.cache = [];
        }
        console.log('/factory');
    })
   .value('applyCacheToResource', function (resource, cache) {
        if (angular.isArray(resource)) {
            resource.splice.apply(resource, [0, 0].concat(cache));
        } else {
            angular.extend(resource, cache);
        }
        return resource;
    })