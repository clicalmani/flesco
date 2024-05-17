<?php 
namespace Clicalmani\Flesco\Support\Facades;

use Clicalmani\Flesco\Support\Facades\Facade;

/**
 * Log Class
 * 
 * @package Clicalmani\Flesco/flesco 
 * @author @Clicalmani\Flesco
 * 
 * @method static void init()
 * @method static void error(string $error_message, ?int $error_level = E_ERROR, ?string $file = 'Unknow', ?int $line = null)
 * @method static void warning(string $warning_message, ?string $file = 'Unknow', ?int $line = null)
 * @method static void notice(string $notice_message, ?string $file = 'Unknow', ?int $line = null)
 * @method static void debug(string $debug_message, ?string $file = 'Unknow', ?int $line = null)
 */
class Log extends Facade
{}
