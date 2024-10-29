var app = angular.module('AddressyWordPress',
    [   
        'rzModule'
    ]);

app.filter("numeral", function () {
    return function (input, format) {
        format = format || (input < 100000 ? '0,0.[000]' : '0.0a');
        return numeral(input).format(format);
    }
});

app.controller( 'keyCtrl', [ 
    '$scope',
    '$window',
    '$timeout',
    function( $scope, $window, $timeout )  {
    //Needs to be the same as in LicensesController.cs from Addressy Website
    var dailyLimitValues = [5, 10, 15, 20, 25, 50, 75, 100, 250, 500, 1000, 999999];
    var individualIpLimitValues = [1, 2, 3, 4, 5, 10, 0];
    //Needs to be the same as in LicensesController.cs from Addressy Website

    $scope.dailyLimitSlider = {
        value: 0,
        floor: 0,
        ceil: dailyLimitValues.length - 1,
        translate: function (index) {
            if ( index == dailyLimitValues.length-1 ) return 'No limit';
            if ( index >= 0 && index < dailyLimitValues.length )
                return numeral( dailyLimitValues[index] ).format( '0,0.[000]' );
            return 'No limit';
        },
        showSelectionBar: true,
        getSelectionBarColor: function () {
            return '#e21b6c'; //CSS Raspberry
        },
        step: 1
    };

    $scope.individualIpLimitSlider = {
        value: 0,
        floor: 0,
        ceil: individualIpLimitValues.length - 1,
        translate: function (index) {
            if ( index == individualIpLimitValues.length - 1 ) return 'No limit';
            if ( index >= 0 && index < individualIpLimitValues.length )
                return numeral( individualIpLimitValues[index] ).format( '0,0.[000]' );
            return 'No limit';
        },
        showSelectionBar: true,
        getSelectionBarColor: function () {
            return '#e21b6c'; //CSS Raspberry
        },
        step: 1
    };
    
    $scope.dailyLimit = $window._dailyLimit;

    $scope.dailyLimitIndex =  get_index(dailyLimitValues, $scope.dailyLimit ) ;
   
    $scope.ipLimit = $window._limitPerUser;

    $scope.ipLimitIndex =  get_index(individualIpLimitValues, $scope.ipLimit ) ;

    function get_index( arrayToSearch, value )
    {
        var limit = arrayToSearch.indexOf(value);
        if ( limit == -1 )
        {
            limit = arrayToSearch.length -1;
        }     
        return limit;
    }

    $scope.refresh_slider = function () {
        $timeout( function doRefresh(){
            $scope.$$postDigest(function () {
                $scope.$broadcast( 'rzSliderForceRender' );
            })
        },500);
    }
    
}
]);

