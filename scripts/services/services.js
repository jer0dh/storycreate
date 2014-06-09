
jh.factory('Model', [ '$log', '$http', 'State', function($log, $http, State) {

    /**
     *  The Model
     *
     */
    // the model
    var data = {};

    // External resources
    var apiStoryUrl = 'http://localhost/api/story/';
    data.logonUrl = './sql/auth/logon/logon.php';
    data.googleSignInUrl = 'http://localhost/storycreate/sql/auth/google/signin.php';

    data.createBlankStory = function() {
        return 		{
            id: 0,
            title: '',
            dateCreated: new Date(),
            dateModified: new Date(),
            description: '',
            isPublic: true,
            storyContent: [	]
        }
    }
    data.createBlankStories = function() {
        return [
            {
                id: 0,
                title: '',
                dateCreated: new Date(),
                dateModified: null,
                description: '',
                isPublic: true,
                storyContent: [	]
            }
        ];
    }

    data.storyList = data.createBlankStories();
    data.story = data.createBlankStory();

    data.getStories = function() {
        State.addAsync();
        $http.get(apiStoryUrl)
            .success(function successGetStories(results){
                data.storyList = results['success'];
                State.decAsync();
            }).
            error(function errorGetStories(message){
                console.log("errorGetStories");
                console.log(message);
                State.decAsync();
            });
    };

    data.getStory = function(id) {
        State.addAsync();
        $http.get(apiStoryUrl + id)
            .success(function successGetStories(results){
                data.story = results['success'];
                State.decAsync();
            }).
            error(function errorGetStories(message){
                console.log("errorGetStory");
                console.log(message);
                State.decAsync();
            });
    };

    data.newStory = function(story, callback) {

        // when save button clicked in view, need save button to disable so that not two calls to newStory.
        // use a callback to run on success to re-enable save button
        // need callback for error? or a single callback that is passed the results so it can determine if error
//        var args = _.toArray(arguments);
//        var callback = null;
//        if (args.length > 0 && typeof args[0] == "function"){
//            callback = args[0];
//        }
        //deep copy of story and changes date formats to strings
        var cStory = JSON.parse(JSON.stringify(story));
        var pdata = {'story' : cStory};
        State.addAsync();
        $http.post(apiStoryUrl, pdata)
            .success(function(results){
                story.id = results['success'].story.id;
                State.decAsync();

                if(callback) { callback();}
            })
            .error(function(message){
                State.decAsync();
                console.warn("errorNewStory");
                console.warn(message);
            });
//TODO: Set storyManager.php to send error back when isset $results['error'] and do something with that.  Do we have separate callbacks if success vs error, or have callback determine if typeof results['error'] != undefined


    };

    data.saveStory = function(story, callback) {
        //deep copy of story and changes date formats to strings
        var cStory = JSON.parse(JSON.stringify(story));
        var pdata = {'story' : cStory};
  //      console.log (JSON.stringify(pdata));
        State.addAsync();
        $http.put(apiStoryUrl, pdata)
            .success(function(results){
                State.decAsync();

                if(callback) { callback();}
            })
            .error(function(message){
                State.decAsync();
                console.warn("errorNewStory");
                console.warn(message);
            })


    };
    data.addNewContent = function(newContent, callback) {
//        args = _.toArray(arguments);
//        return Storage.addNewContent.apply(null, args);

        data.story.storyContent.push({
            userId          : State.auth.currentUserId,
            content         : newContent,
            date            : new Date(),
            userName        : State.auth.currentUser
        });

        data.saveStory(data.story, callback);
    };

    data.deleteStory = function(id, callback) {

        State.addAsync();
        $http.delete(apiStoryUrl + id)
            .success(function(results){
                State.decAsync();

                if(callback) { callback();}
            })
            .error(function(result){
                State.decAsync();
                console.warn("errorDeleteStory");
                console.warn(result['error']);
            })
    };
    return data;
}]);




jh.factory('State', [ '$log', '$http', '$location', function($log, $http, $location){

    stateMap = {
        currentController   : "",
        isSaving            : false,
        asyncCount          : 0,
        page                : "landing",
        test                : 0
    };

    stateMap.auth = {
        accessToken                 : null,
        thirdPartyAccessToken       : null,
        currentUser                 : "tweedb",
        currentUserId               : 2,

        isAuth                      : function() {
            return this.accessToken ? true : false;
        },
        init                        : function(scAccessToken, userId, userName, thirdPartyAccessToken) {
            this.accessToken = scAccessToken;
            this.currentUserId = userId;
            this.currentUser = userName;
            if (typeof(thirdPartyAccessToken) !== 'undefined') {
                this.thirdPartyAccessToken = thirdPartyAccessToken;
            }
            $http.defaults.headers.common['Authorization'] = "Bearer " + this.accessToken;
            // -Determined goDaddy php could not find this header (did not have apache_request_headers())
            //   even when finding a function to manually recreate the apache_request_headers function
            // adding the following to .htaccess in local env and on godaddy allowed authorization header
            // # Pass Authorization headers to an environment variable RewriteRule .* - [E=HTTP_Authorization:%{HTTP:Authorization}]
        },
        disconnect                  : function() {
            this.accessToken = null;
            this.thirdPartyAccessToken = null;
            this.currentUser = "";
            this.currentUserId = "";
            $http.defaults.headers.common['Authorization'] = undefined;
            $location.path('/login');
        }
    };
    stateMap.addAsync = function () {
        stateMap.asyncCount++;
    };

    stateMap.decAsync = function (){
        stateMap.asyncCount--;
    };

    return stateMap;
}]);