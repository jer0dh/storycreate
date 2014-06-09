
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
                    State.auth.init(result['scAccessToken'], result['id'], result['user_name'],'google-' + authResult['access_token']);
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
    $scope.renderProfile = function() {
        var request = gapi.client.plus.people.get( {'userId' : 'me'} );
        request.execute( function(profile) {
            $('#profile').empty();
            if (profile.error) {
                $('#profile').append(profile.error);
                return;
            }
            $('#profile').append(
                $('<p><img src=\"' + profile.image.url + '\"></p>'));
            $('#profile').append(
                $('<p>Hello ' + profile.displayName + '!<br />Tagline: ' +
                    profile.tagline + '<br />About: ' + profile.aboutMe + '</p>'));
            if (profile.cover && profile.coverPhoto) {
                $('#profile').append(
                    $('<p><img src=\"' + profile.cover.coverPhoto.url + '\"></p>'));
            }
        });
    };
});

jh.controller('LoginController', function($location, $scope, $http, Model, State){
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
                State.auth.init(result['scAccessToken'], result['id'], result['user_name']);
                $location.path('/list');
            })
            .error(function(e){
                console.log(e);
            });

    };

});

//TODO: Determine login page behavior: Automatically redirect if logged in.  Disconnect thru User menu only.  If so, remove ng-init on Google\index.html page
jh.controller('MainController', function($scope, Model, State) {

    $scope.model = Model;
    $scope.state = State;
    State.page = "";
    console.log(JSON.stringify(State));

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