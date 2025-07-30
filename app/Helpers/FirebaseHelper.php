<?php

namespace App\Helpers;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class FirebaseHelper
{
    public static function verifyIdToken($idToken)
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'));

        $auth = $factory->createAuth();

        try {
            $verifiedToken = $auth->verifyIdToken($idToken);
            return $verifiedToken->claims()->all();
        } catch (FailedToVerifyToken $e) {
            return null;
        }
    }
}

?>