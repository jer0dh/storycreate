
jh.factory('Model', [ '$log', '$http', 'State', function($log, $http, State) {

    /**
     *  The Model
     *
     */
    // the model
    var data = {};

    // Resources used in model (private)
    var apiStoryUrl = 'http://localhost/api/story/';

    // Resources used in app
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
    };
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
                data.storyList = results['success'];
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
                data.story = results['success'];
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

    /**
     * $http PUT request to RESTful server with data containing story.
     * An optional callback is called on success.
     * @param story
     * @param callback
     */
    data.saveStory = function(story, callback) {
        //deep copy of story and changes date formats to strings
        var cStory = JSON.parse(JSON.stringify(story));
        var pdata = {'story' : cStory};

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

    /**
     * Adds new content to story by pushing it onto array.  Calls saveStory to save on server
     * An optional callback is passed to the saveStory function
     *
     * @param newContent
     * @param callback
     */
    data.addNewContent = function(newContent, callback) {

        data.story.storyContent.push({
            userId          : State.auth.currentUserId,
            content         : newContent,
            date            : new Date(),
            userName        : State.auth.currentUser
        });

        data.saveStory(data.story, callback);
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
        currentUser                 : "",
        currentUserId               : 0,

        // simple function for controllers to determine if user logged in
        // RESTful server will validate access token during an requests to it
        isAuth                      : function() {
            return this.accessToken ? true : false;
        },

        // called after successful login or after a refresh when $window.sessionStorage has existing
        // login data.
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

            //save this to session in case of browser refresh
            $window.sessionStorage.setItem('storyCreateAuth', JSON.stringify(this));
            console.log(JSON.stringify(this));
        },

        // removes all local logon information
        disconnect                  : function() {
            this.accessToken = null;
            this.thirdPartyAccessToken = null;
            this.currentUser = "";
            this.currentUserId = "";
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
            stateMap.auth.init(auth.accessToken, auth.currentUserId, auth.currentUser, auth.thirdPartyAccessToken);
        }
    }
    init();
    return stateMap;
}]);