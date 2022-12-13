<?php
namespace Clicalmani\Flesco\Notifications;

use Clicalmani\Flesco\Database\DB;

class Notification 
{
	
	static function push($message) 
	{
		DB::raw('SELECT pushNotification("' . $message . '")');
	}
}
?>