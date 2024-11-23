<?php

namespace Maya\Support;

use App\Models\User;

class Auth
{
    private static string $redirectRouteName = "login";


    public static function user()
    {
        if (!session('user')) {
            redirect()->route(self::$redirectRouteName)->exec();
        }
        $user = User::find(session('user'));
        if (empty($user)) {
            session()->forget('user');
            redirect()->route(self::$redirectRouteName)->exec();
        } else {
            return $user;
        }
    }

    public static function id()
    {
        return self::user()->id;
    }

    public static function check()
    {
        if (!session('user')) {
            redirect()->route(self::$redirectRouteName)->exec();
        }
        $user = User::find(session('user'));
        if (empty($user)) {
            session()->forget('user');
            redirect()->route(self::$redirectRouteName)->exec();
        } else
            return true;
    }

    public static function checkLogin(): bool
    {
        if (!session('user')) {
            return false;
        }
        $user = User::find(session('user'));
        if (empty($user)) {
            return false;
        } else
            return true;
    }

    public static function logout(): void
    {
        session()->forget('user');
    }
}
