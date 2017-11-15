var casada = angular.module('casada', []);

casada.controller("CouponCodeChecker", ['$scope','$http',function($scope,$http) 
{
	 $scope.coupon_status 	= '';
	 $scope.coupon_code 	= '';
	 var check_result 		= false;

	 $scope.check = function()
	 {
	 	var msg = false;

	 	if( !$scope.coupon_code ) {
	 		$scope.coupon_status = false;
	 		return false;
	 	}

	 	

	 	$http({
	 		method : "post",
	 		url: "/ajax/get/",
            data: {
                type: "checkCouponCodeUsage",
                code: $scope.coupon_code
            }

	 	}).then(
	 		// Success
	 		function(re){
	 			console.log('Success');
	 			console.log(re.data);
	 			check_result = re;
	 		},
	 		// Error
	 		function(re){
	 			console.log('Error');
	 			console.log(re);
	 		}
	 	);

	 	if( check_result )
	 	{
	 		msg = 'Szabad';
	 	} else {
	 		msg = false;
	 	}
		 
		//$scope.coupon_status = msg;	
	 }
}]);