
jh.config(function ($routeProvider) {
	$routeProvider.
        when('/', {
			controller: 'MainController',
			templateUrl: './views/landing.html'
		}).
		when('/list', {
			controller: 'ListController',
			templateUrl: './views/listStories.html'
		}).
		when('/viewStory/:id', {
			controller: 'ViewStoryController',
			templateUrl: './views/viewStory.html'
		}).
		when('/editStory/:id', {
			controller: 'EditStoryController',
			templateUrl: './views/editStory.html'
		}).
        when('/login', {
            controller: 'GPlusController',
            templateUrl: './views/login.html'
        }).
//			when('/login', {
//			controller: 'LoginController',
//			templateUrl: './views/login.html'
//		}).
//			when('/register', {
//			controller: 'RegisterController',
//			templateUrl: './views/register.html'
//		}).
		otherwise({
			redirectTo: '/'
		});

});
