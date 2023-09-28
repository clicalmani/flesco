<?php
namespace Clicalmani\Flesco\Notifications;

use Clicalmani\Database\DB;

class Notification 
{
	
	static function push($message) 
	{
		DB::raw('SELECT pushNotification("' . $message . '")');
	}
}
?>