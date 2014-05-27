
jh.controller('GPlusController', function($scope, Model, State, $location) {

    $scope.model = Model;
    $scope.state = State;
    State.page = "";
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
        console.log(authResult.code);
        console.log("in connectServer:");
        $.ajax({
            type: 'POST',
            url: 'http://localhost/storycreate/sql/auth/signin.php' + '/connect?state=' + afToken,
            contentType: 'application/octet-stream; charset=utf-8',
            success: function(result) {
                console.log("success");
                console.log(result);
                gapi.client.load('plus','v1',$scope.renderProfile);
                $('#authOps').show('slow');
                $('#gConnect').hide();
            },
            error: function(e, m, a) {
                console.log('error in connectServer');
                console.log(e);
                console.log(m);
                console.log(a);
            },
            processData: false,
            data: authResult.code
        });
    };
    $scope.disconnect = function() {
        // Revoke the server tokens
        $.ajax({
            type: 'POST',
            url: 'http://localhost/storycreate/sql/auth/signin.php' + '/disconnect',
            async: false,
            success: function(result) {
                console.log('revoke response: ' + result);
                $('#authOps').hide();
                $('#profile').empty();
                $('#visiblePeople').empty();
                $('#authResult').empty();
                $('#gConnect').show();
            },
            error: function(e) {
                console.log(e);
            }
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
jh.controller('MainController', function($scope, Model, State) {

    $scope.model = Model;
    $scope.state = State;
    State.page = "";

});

jh.controller('ListController', function($scope, Model, State, $location) {

    $scope.model = Model;
    $scope.state = State;
    State.page = "list";
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

jh.controller('ViewStoryController', function($scope, Model, State, $routeParams) {

    $scope.model = Model;
    $scope.state = State;
    State.page = "view";

    var id = $routeParams['id'];
    Model.getStory(id);
});