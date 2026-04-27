<?php

namespace App\Services;

use Kreait\Firebase\Factory;

class FirebaseService
{
    protected $factory;

    public function __construct()
    {
        $this->factory = (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->withDatabaseUri(config('firebase.database.url'));
    }

    public function database()
    {
        return $this->factory->createDatabase();
    }

    public function auth()
    {
        return $this->factory->createAuth();
    }

    public function messaging()
    {
        return $this->factory->createMessaging();
    }
}
