<?php
namespace Clicalmani\Flesco\Providers;

abstract class AuthServiceProvider extends ServiceProvider
{
    public static function userAuthenticator()
	{
		return @ static::$kernel['auth']['user'];
	}
}
