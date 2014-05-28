<?php
/**
 * This is just a section of the signin.php file since the other parts contain secret key etc.
 *
 * User: jerod
 * Date: 5/27/14
 * Time: 9:04 PM
 */


$app->post('/connect', function (Request $request) use ($app, $client, $plus) {  //added $plus - jer0dh
    $token = $app['session']->get('token');

    if (empty($token)) {
        // Ensure that this is no request forgery going on, and that the user
        // sending us this connect request is the user that was supposed to.
        if ($request->get('state') != ($app['session']->get('state'))) {
            return new Response('Invalid state parameter', 401);
        }

        // Normally the state would be a one-time use token, however in our
        // simple case, we want a user to be able to connect and disconnect
        // without reloading the page.  Thus, for demonstration, we don't
        // implement this best practice.
        //$app['session']->set('state', '');

        $code = $request->getContent();
        // Exchange the OAuth 2.0 authorization code for user credentials.
        $client->authenticate($code);
        $token = json_decode($client->getAccessToken());

        // You can read the Google user ID in the ID token.
        // "sub" represents the ID token subscriber which in our case
        // is the user ID. This sample does not use the user ID.
        $attributes = $client->verifyIdToken($token->id_token, CLIENT_ID)
            ->getAttributes();
        $gplus_id = $attributes["payload"]["sub"];

        // Store the token in the session for later use.
        $app['session']->set('token', json_encode($token));
        //*****************************Begin jer0dh Addition****************************
        //Get name from google
        //    $client->setAccessToken($token);
        $people = $plus->people->get('me');

        // for my app
        $googleMeta = array(
            'access-token'      =>  $token->access_token,
            'id'                =>  $gplus_id,
            'timeout'           =>  $token->expires_in,
            'name'              =>  $people['displayName']
        );

        // my functions to login user to my app with google info.

        // check to make sure access-token is not in authusers table, delete if is
        if (existingAccessToken($googleMeta['access-token'])){
            deleteAccessToken($googleMeta['access-token']);
        }
        // check to make sure existing google ID not in Authusers table, delete if is
        if(existingAuthUsersId($googleMeta['id'])){
            deleteAuthUsersId($googleMeta['id']);
        }
        $storyAccessToken = addAuthUser($googleMeta);

        // check to see if google id is in users table
        if (! existingId($googleMeta['id'])){
            // add user
            addUser($googleMeta);
            if (! existingId($googleMeta['id'])){
                return new Response('Unable to add user to '. DB_USERS . ' in table ' . TB_USERS, 401);
            }
        }

        // all should be good here so return success
        // ******************************END jer0dh addition *********************************************



        $response = 'Success:' . $storyAccessToken;
    } else {
        $response = 'Already connected';
    }

    return new Response($response, 200);
});