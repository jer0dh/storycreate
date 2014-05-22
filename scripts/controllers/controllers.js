
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