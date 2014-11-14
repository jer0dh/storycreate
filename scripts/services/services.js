jh.factory('UserModel', [ '$http', 'State', function($http, State){

    var user = {};
    var apiUserUrl = "http://localhost:8080/StoryCreate/api/login/";
    var apiMeUrl = "http://localhost:8080/StoryCreate/api/me";
    user.currentUser = {
        userId          :  0,
        userEmail       : null,
        userName        : null,
        imageUrl        : null
    };

    user.updateUser = function(user, sCallback, eCallback) {
        //deep copy of story and changes date formats to strings
        var cUser = JSON.parse(JSON.stringify(user));

        State.addAsync();
        $http.put(apiUserUrl, cUser)
            .success(function(results){
                State.decAsync();
                console.log("results of PUT");
                console.log(results);
                if(sCallback) { sCallback();}
            })
            .error(function(message){
                State.decAsync();

                if(eCallback) { eCallback();}
                console.warn("errorNewStory");
                console.warn(message);
            })
    };

    user.getMyUserSettings = function(userId, sCallback, eCallback) {
        State.addAsync();
        console.log("getMyUserSettings called with userId = " + userId);
        $http.get(apiMeUrl)
            .success(function successGetStories(results){
                console.log(results);
                State.auth.userId = results.id;
                State.auth.userName = results.username;
                State.auth.userEmail = results['success'].user_email;
                State.auth.imageUrl = results['success'].image_url;
                console.log(State.auth.userEmail);
                State.decAsync();
                if(sCallback) { sCallback();}
            }).
            error(function errorGetStories(message){
                console.log("errorGetStory");
                console.log(message);
                State.decAsync();
                if(eCallback) { eCallback();}
            });
    };

    return user;
}]);

jh.factory('Model', [ '$log', '$http', 'State', function($log, $http, State) {

    /**
     *  The Model
     *
     */
    // the model
    var data = {};

    // Resources used in model (private)
    var apiStoryUrl = 'http://localhost:8080/StoryCreate/api/story/';
    var apiStoryContentUrl = 'http://localhost:8080/StoryCreate/api/storyContent';

    // Resources used in app
    data.logonUrl = 'http://localhost:8080/StoryCreate/api/login';
    data.googleSignInUrl = 'http://localhost/storycreate/sql/auth/google/signin.php';

    data.createBlankStory = function() {
        return 		{
            id: 0,
            title: '',
            dateCreated: new Date(),
            lastUpdated: new Date(),
            description: '',
            isPublic: true,
            storyContent: [	]
        }
    };
    data.createBlankStories = function() {
        return [
            {
                id: 0,
                title: '',
                owner: {id: null, name: null},
                dateCreated: new Date(),
                lastUpdated: null,
                description: '',
                isPublic: true,
                storyContent: [	]
            }
        ];
    };

    // since most calls for data are asynchronous we need these blank objects for angular to use
    // for data binding.  The asynchronous calls will eventually populate the object and thanks
    // to angular's data binding, the view will be populated as well.
    data.storyList = data.createBlankStories();
    data.story = data.createBlankStory();

    /**
     * $http GET request to RESTful API url that will populate data.storyList
     */
    data.getStories = function() {
        State.addAsync();
        $http.get(apiStoryUrl)
            .success(function successGetStories(results){
                data.storyList = results;
                State.decAsync();
            }).
            error(function errorGetStories(message){
                console.log("errorGetStories");
                console.log(message);
                State.decAsync();
            });
    };
    /**
     * $http GET request to RESTful API url that will populate data.story with story of 'id'
     * @param id
     */
    data.getStory = function(id) {
        State.addAsync();
        $http.get(apiStoryUrl + id)
            .success(function successGetStories(results){
                data.story = results;
                State.decAsync();
            }).
            error(function errorGetStories(message){
                console.log("errorGetStory");
                console.log(message);
                State.decAsync();
            });
    };

    /**
     * $http POST request to RESTful server with POST data containing a new story.
     * An optional callback is called on success.
     * @param story
     * @param callback
     */
    data.newStory = function(story, callback) {
        var cStory = JSON.parse(JSON.stringify(story));
        State.addAsync();
        $http.post(apiStoryUrl, cStory)
            .success(function(results){
                story.id = results.id;
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

    /**
     * $http PUT request to RESTful server with data containing story.
     * An optional callback is called on success.
     * @param story
     * @param callback
     */
    data.saveStory = function(story, callback) {
        //deep copy of story and changes date formats to strings
        var cStory = JSON.parse(JSON.stringify(story));

        State.addAsync();
        $http.put(apiStoryUrl, cStory)
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
    data.saveNewStoryContent = function(newStoryContent, callback) {
        State.addAsync();
        $http.post(apiStoryContentUrl, newStoryContent)
            .success(function(results){
                State.decAsync();
                data.story.storyContent.push(newStoryContent);
                if(callback) {callback(); }
            })
            .error(function(message) {
                State.decAsync();
                console.warn("errorNewStoryContent");
                console.warn(message);
            })
    };

    /**
     * Adds new content to story by pushing it onto array.  Calls saveStory to save on server
     * An optional callback is passed to the saveStory function
     *
     * @param newContent
     * @param callback
     */
    data.addNewContent = function(newContent, callback) {
//TODO Need to pass in story id with content
        var newStoryContent = {
            author           : {id : State.auth.userId, userName: State.auth.userName},
            content         : newContent,
            dateCreated     : new Date()
        };


        data.saveNewStoryContent(newStoryContent, callback);
    };

    /**
     * $http DELETE request to RESTful server with data story id.
     * An optional callback is called on success.
     *
     * @param id
     * @param callback
     */
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




jh.factory('State', [ '$log', '$http', '$location', '$window', function($log, $http, $location, $window){

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
        userName                 : "",
        userId               : 0,
        userEmail            : null,
        imageUrl                    : null,

        // simple function for controllers to determine if user logged in
        // RESTful server will validate access token during an requests to it
        isAuth                      : function() {
            return this.accessToken ? true : false;
        },

        // called after successful login or after a refresh when $window.sessionStorage has existing
        // login data.
        init                        : function(obj) {
            this.accessToken = obj.scAccessToken;
            this.userId = obj.userId;
            this.userName = obj.userName;
            if (typeof(obj.thirdPartyAccessToken) !== 'undefined') {
                this.thirdPartyAccessToken = obj.thirdPartyAccessToken;
            }
            if (typeof(obj.userEmail) !== 'undefined') {
                this.userEmail = obj.userEmail;
            }
            $http.defaults.headers.common['Authorization'] = "Bearer " + this.accessToken;
            // -Determined goDaddy php could not find this header (did not have apache_request_headers())
            //   even when finding a function to manually recreate the apache_request_headers function
            // adding the following to .htaccess in local env and on godaddy allowed authorization header
            // # Pass Authorization headers to an environment variable RewriteRule .* - [E=HTTP_Authorization:%{HTTP:Authorization}]

            //save this to session in case of browser refresh
            $window.sessionStorage.setItem('storyCreateAuth', JSON.stringify(this));
            console.log(JSON.stringify(this));
        },

        // removes all local logon information
        disconnect                  : function() {
            this.accessToken = null;
            this.thirdPartyAccessToken = null;
            this.userName = "";
            this.userId = "";
            $http.defaults.headers.common['Authorization'] = undefined;
            $window.sessionStorage.clear();
            $location.path('/login');
        }

        //todo: add php function to be called to remove scAccessToken from sql database or add function to remove old entries
    };
    // called whenever model makes an asych call - to be used to create a spinner or some other indication
    stateMap.addAsync = function () {
        stateMap.asyncCount++;
    };
    // called whenever model returns from an asynch call
    stateMap.decAsync = function (){
        stateMap.asyncCount--;
    };
    // checks for existing session if browser refresh.
    function init() {
        if($window.sessionStorage.getItem('storyCreateAuth')){
            var auth = JSON.parse($window.sessionStorage.getItem('storyCreateAuth'));
            var obj = {
                scAccessToken : auth.accessToken,
                userId      : auth.userId,
                userName    : auth.userName,
                thirdPartyAccessToken: auth.thirdPartyAccessToken,
                userEmail   : auth.userEmail
            }
            stateMap.auth.init(obj);
         //   UserModel.getMyUserSettings(auth.userId);
        }
    }
    init();
    return stateMap;
}]);