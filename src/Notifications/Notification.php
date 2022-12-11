<?php
namespace Cliclamani\Flesco\Notifications;

use Cliclamani\Flesco\Database\DB;

class Notification 
{
	
	static function push($message) 
	{
		DB::raw('SELECT pushNotification("' . $message . '")');
	}
}
?>