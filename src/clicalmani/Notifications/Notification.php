<?php
namespace Flesco\Notifications;

use Flesco\Database\DB;

class Notification 
{
	
	static function push($message) 
	{
		DB::raw('SELECT pushNotification("' . $message . '")');
	}
}
?>