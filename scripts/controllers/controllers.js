
jh.controller('GPlusController', function($scope, Model, State, $location, $http) {

    $scope.model = Model;
    $scope.state = State;
    State.page = "login";
 //   $scope.afToken = document.getElementById('afToken').getAttribute('data-afToken');
 //   var $div = document.getElementById('theState');
  //  $scope.theState = $div.getAttribute("data-state");
  //  console.log($scope.theState);


    $scope.test = function(){
        State.test = State.test + 1;
        console.log("Test is " + State.test);
        $location.path('/');
    };
    $scope.onSignInCallback = function(authResult, afToken){
        console.log('controller code got called!');
        console.log(authResult);
        State.gpplus = {};
        State.gpplus.accessToken = authResult['access_token'];
        console.log("in connectServer:");
        $http({
            method: 'POST',
            url: Model.googleSignInUrl + '/connect?state=' + afToken,
            headers: {'Content-type': 'application/octet-stream; charset=utf-8'},
            data : authResult.code }).
            success(function(result) {
                console.log("success");
                console.log(result);
                if (typeof(result['message']) === 'undefined') { // if not already signed in
                    var obj = {
                        scAccessToken : result.scAccessToken,
                        userId      : result.id,
                        userName    : result.user_name,
                        thirdPartyAccessToken: 'google-' + result['access_token']
                    };
                    if (! typeof(result['user_email'])==='undefined') {
                        obj['userEmail'] = result['user_email'];
                    }
                    State.auth.init(obj);
                    $('#authOps').show('slow');
                    $location.path('/list');
                } else {
                    $scope.disconnect();
                    // google token found on machine but php server needs one-time code which is only
                    // obtained at initial logon.  This is called when the page is initially called
                    // because google has the \connect post called when signin button is rendered not just
                    // when pressed.
                }
            }).
            error(function(e, m, a) {
                console.log('error in connectServer');
                console.log(e);
                console.log(m);
                console.log(a);
            });


    };
    $scope.disconnect = function() {
        State.auth.disconnect();
        // Revoke the server tokens
        $http({
            method: 'POST',
            url:  Model.googleSignInUrl  + '/disconnect'}).
            success(function(result) {
                console.log('revoke response: ' + result);
                $('#authOps').hide();
                $('#profile').empty();
                $('#visiblePeople').empty();
                $('#authResult').empty();
//                $('#gConnect').show();
            }).
            error(function(e) {
                console.log(e);
            });
    };

});

jh.controller('LoginController', function($location, $scope, $http, Model, State,UserModel){
    $scope.model = Model;
    $scope.state = State;
    State.page = "login";

    $scope.error = false;
    $scope.errorMessage = "";
    $scope.usn='';
    $scope.pass='';
    $scope.alerts=[];

    $scope.closeAlert = function(index) {
        $scope.alerts.splice(index,1);
    };

    $scope.login = function(user, password){
        var data;
        data = {'user_name':user, 'user_password': password, 'login': 1};
        console.log(data);
        $http.post(Model.logonUrl, data)
            .success(function(result) {
                console.log('LoginController: Success');
                console.log(result);
                var obj = {
                    scAccessToken: result['scAccessToken'],
                    userId : result['id'],
                    userName : result['user_name']
                };
                if (! typeof(result['user_email'])==='undefined') {
                    obj['userEmail'] = result['user_email'];
                }
                State.auth.init(obj);
                UserModel.getMyUserSettings(State.auth.userId);
                $location.path('/list');
            })
            .error(function(e){
                console.log(e);
            });

    };

});
jh.controller('ModalInstanceCtrl', function($scope, $modalInstance, items, State, UserModel){

    $scope.items = State.auth;
    console.log('modalinstancectrl');
    console.log(items);
    originalItems = _.clone(items);

    $scope.okSuccess = function(){
        State.auth.userName = $scope.items.userName;
        State.auth.userEmail = $scope.items.userEmail;
        State.imageUrl = $scope.items.imageUrl;
        $modalInstance.close();
    };
    $scope.okError = function(e) {
        console.log('error saving User:');
        console.log(e);
    };
    $scope.ok = function () {
        // check if data changed
        if (!(_.isEqual(originalItems, $scope.items))){
            // if username changed, check if existing, alert if so and exit function
            //    if not existing, save user, update state's username and image
            // if email/image changed save user
            var user = {};
            user.user_name = $scope.items.userName;
            user.user_email = $scope.items.userEmail;
            user.image_url = $scope.items.imageUrl;
            user.userId = State.auth.userId;

            UserModel.updateUser(user, $scope.okSuccess, $scope.okError);

        } else {
            $modalInstance.close();
        }


    };

    $scope.cancel = function () {
        $modalInstance.dismiss('canceled by user');
    };


});
//TODO: Determine login page behavior: Automatically redirect if logged in.  Disconnect thru User menu only.  If so, remove ng-init on Google\index.html page
jh.controller('MainController', function($scope, Model, State, $modal) {

    $scope.model = Model;
    $scope.state = State;
    State.page = "";

    $scope.items = {};

    // User Settings Modal
    $scope.settings = function() {
        $scope.items.userEmail = State.auth.userEmail;
        $scope.items.userName = State.auth.userName;
        $scope.items.imageUrl = State.imageUrl;
        console.log("in settings()");
        console.log($scope.items);
        var modalInstance = $modal.open({
            templateUrl : 'userSettingsContent.html',
            controller: 'ModalInstanceCtrl',
            size: 'lg',
            resolve: { items : function() {
                return $scope.items;
                }
            }

        });

        modalInstance.result.then(function(){
            console.log('User Settings Modal closed with ok');
        }, function(e){
            console.log('User Settings Modal closed with cancel. e = ' + e);
        });
    };


});



jh.controller('ListController', function($scope, Model, State, $location) {

    $scope.model = Model;
    $scope.state = State;
    State.page = "list";
    if (!State.auth.isAuth()){
        $location.path('/login');
    }
    Model.getStories();

    $scope.newStory = function() {
        $location.path('/editStory/0');
    }

    $scope.deleteStory = function(id){
        Model.deleteStory(id, function() {
            Model.getStories();
        });

    }

});

jh.controller('EditStoryController', function($scope, Model, State, $routeParams, $location) {

    $scope.model = Model;
    $scope.state = State;
    if (!State.auth.isAuth()){
        $location.path('/login');
    }
    State.page = "edit";
    $scope.ctrlState = {};
    $scope.ctrlState.isSaving = false;
    $scope.ctrlState.newContent = "";

    function afterSavingNewStory(){
        $location.path('/editStory/' + Model.story.id);
        $scope.ctrlState.isSaving = false;
    }

    function afterSavingStory() {
        $scope.ctrlState.isSaving = false;
        $scope.ctrlState.newContent = "";
    }


    var id = $routeParams['id'];
    if(id == 0){
        $scope.ctrlState.isNew = true;
        Model.story = Model.createBlankStory();
    } else {
        $scope.ctrlState.isNew = false;
        Model.getStory(id);
//TODO: Determine what happens when id does not exist
    }

    $scope.save = function (){
        $scope.ctrlState.isSaving = true;
        if ($scope.ctrlState.isNew) {
            Model.newStory(Model.story, afterSavingNewStory );
        } else {
            Model.saveStory(Model.story, afterSavingStory);
        }
    }

    $scope.saveNewContent = function() {
        $scope.ctrlState.isSaving = true;
        Model.addNewContent($scope.ctrlState.newContent, afterSavingStory);

    }

});

jh.controller('ViewStoryController', function($scope, Model, State, $routeParams, $location) {

    $scope.model = Model;
    $scope.state = State;
    State.page = "view";
    if (!State.auth.isAuth()){
        $location.path('/login');
    }
    var id = $routeParams['id'];
    Model.getStory(id);
});