<?php
namespace Clicalmani\Flesco\Facade;

use Clicalmani\Database\DB;
use Clicalmani\Flesco\Misc\RecursiveFilter;

class Tonka extends Facade
{
    public static function migrate(bool $fresh = true, bool $seed = true, bool $routines = true) : bool
    {
        $migrations_dir = new \RecursiveDirectoryIterator( database_path('/migrations') );
        $filter = new RecursiveFilter($migrations_dir);

        if ($fresh) {
            try {
                self::writeln('Cleaning the database ...');
                
                foreach ($filter->getFiles() as $filename => $pathname) {
                    $migration = require $pathname;
    
                    if ( method_exists($migration, 'out') ) {
                        self::writeln('Droping ' . $filename);
                        $migration->out();
                        self::writeln('success');
                    }
                }
            } catch (\PDOException $e) {
                self::writeln('Failure');
                self::writeln($e->getMessage());
    
                return false;
            }
        }

        try {
            self::writeln('Migrating the database ...');

            foreach ($filter->getFiles() as $filename => $pathname) {
                $migration = require $pathname;

                if ( method_exists($migration, 'in') ) {
                    self::writeln('Migrating ' . $filename);
                    $migration->in();
                    self::writeln('success');
                }
            }
        } catch (\PDOException $e) {
            self::writeln('Failure');
            self::writeln($e->getMessage());

            return false;
        }

        if ( $seed ) {
            $success = self::seed();

            if (false == $success) return false;
        }

        if ( $routines ) {
            $success = self::routineFunctions();

            if (false == $success) return false;

            $success = self::routineProcs();

            if (false == $success) return false;
            
            $success = self::routineViews();

            if (false == $success) return false;
        }

        return true;
    }

    public static function seed(string $class = null) : bool
    {
        if ($class) {
            require_once database_path("/seeders/$class.php");

            $seeder = new $class;

            self::writeln('Running ' . $class);

            if ( self::runSeed($seeder) ) {
                self::writeln('success');

                return true;
            }

            self::writeln('failure');

            return false;
        }

        try {
            $seeders_dir = new \RecursiveDirectoryIterator( database_path('/seeders') );

            self::writeln('Seeding the database');

            foreach (new \RecursiveIteratorIterator($seeders_dir) as $file) { 
                $pathname = $file->getPathname();

                if($file->isFile()) {
                    $filename = $file->getFileName();
                    $class = substr($filename, 0, strlen($filename) - 4); 
                    
                    if(is_readable($pathname)) {
                        require database_path("/seeders/$class.php");

                        $classNs = "\Database\Seeders\\$class";
                        $seeder = new $classNs;

                        self::writeln('Seeding ' . $class);

                        if ( self::runSeed($seeder) ) {
                            self::writeln('success');
                        } else {
                            self::writeln('failure');
                        }
                    }
                }
            }

            return true;

        } catch(\PDOException $e) {
            self::writeln($e->getMessage());
            return false;
        }
    }

    public static function routineFunctions()
    {
        try {
            $functions_dir = new \RecursiveDirectoryIterator( database_path('/routines/functions'));

            self::writeln('Migration routine functions ...');

            foreach (new \RecursiveIteratorIterator($functions_dir) as $file) { 
                $pathname = $file->getPathname();
                $filename = $file->getFileName();

                if($file->isFile()) {
                    if(is_readable($pathname)) {
                        $function = require $pathname;
                        self::writeln("Creating $filename ...");
                        self::drop($filename, 'FUNCTION');
                        
                        if (false == self::create($function)) {
                            self::writeln('Failure');
                        } else self::writeln('success');
                    }
                }
            }

            return true;

        } catch(\PDOException $e) {
            self::writeln($e->getMessage());
            return false;
        }
    }

    public static function routineProcs()
    {
        try {
            $procedures_dir = new \RecursiveDirectoryIterator( database_path('/routines/procedures') );

            self::writeln('Migration stored procedures ...');

            foreach (new \RecursiveIteratorIterator($procedures_dir) as $file) { 
                $pathname = $file->getPathname();
                $filename = $file->getFileName();

                if($file->isFile()) {
                    if(is_readable($pathname)) {
                        $function = require $pathname;
                        self::writeln("Creating $filename ...");
                        self::drop($filename, 'PROCEDURE');
                        
                        if (false == self::create($function)) {
                            self::writeln('Failure');
                        } else self::writeln('success');
                    }
                }
            }

            return true;

        } catch(\PDOException $e) {
            self::writeln($e->getMessage());
            return false;
        }
    }

    public static function routineViews()
    {
        try {
            $views_dir = new \RecursiveDirectoryIterator( database_path('/routines/views') );

            self::writeln('Migration routine views ...');

            foreach (new \RecursiveIteratorIterator($views_dir) as $file) { 
                $pathname = $file->getPathname();
                $filename = $file->getFileName();

                if($file->isFile()) {
                    if(is_readable($pathname)) {
                        $function = require $pathname;
                        self::writeln("Creating $filename ...");
                        self::drop($filename, 'VIEW');
                        
                        if (false == self::create($function)) {
                            self::writeln('Failure');
                        } else self::writeln('success');
                    }
                }
            }

            return true;

        } catch(\PDOException $e) {
            self::writeln($e->getMessage());
            return false;
        }
    }

    /**
     * Create symbolic link
     * 
     * @param string $target Target of the link
     * @param string $link Link name
     * @return bool
     */
    public static function link(string $target, string $link) : bool
    {
        return symlink($target, $link);
    }

    private static function runSeed(mixed $seeder) : bool
    {
        try {
            $seeder->run();
            return true;
        } catch(\PDOException $e) {
            self::writeln($e->getMessage());
            return false;
        }
    }

    private static function writeln($message = '')
    {
        printf("%s", $message);
        print("<br/>");
    }

    private static function create(?callable $function) : bool
    {
        try {
            $sql = str_replace('%DB_TABLE_PREFIX%', $_ENV['DB_TABLE_PREFIX'], $function());
            DB::getInstance()->query($sql);
            return true;
        } catch(\PDOException $e) {
            self::writeln($e->getMessage());
            return false;
        }
    }

    private static function drop(string $name, string $type) : void
    {
        DB::getInstance()->query("DROP $type IF EXISTS `$name`");
    }
}
