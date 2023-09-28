<?php
namespace Clicalmani\Flesco\Facade;

class Tonka extends Facade
{
    public static function migrate(bool $fresh = true, $seed = true)
    {
        $migrations_dir = new \RecursiveDirectoryIterator( database_path('/migrations') );

        try {
            self::writeln('Cleaning the database ...');

            foreach (new \RecursiveIteratorIterator($migrations_dir) as $file) { 
                $pathname = $file->getPathname();

                if($file->isFile()) {
                    $filename = $file->getFileName(); 
                    
                    if(is_readable($pathname)) {
                        $migration = require $pathname;

                        if ( method_exists($migration, 'out') ) {
                            self::writeln('Droping ' . $filename);
                            $migration->out();
                            self::writeln('success');
                        }
                    }
                }
            }
        } catch (\PDOException $e) {
            self::writeln('Failure');
            self::writeln($e->getMessage());

            return false;
        }

        try {
            self::writeln('Migrating the database ...');

            foreach (new \RecursiveIteratorIterator($migrations_dir) as $file) { 
                $pathname = $file->getPathname();

                if($file->isFile()) {
                    $filename = $file->getFileName(); 
                    
                    if(is_readable($pathname)) {
                        $migration = require $pathname;

                        if ( method_exists($migration, 'in') ) {
                            self::writeln('Migrating ' . $filename);
                            $migration->in();
                            self::writeln('success');
                        }
                    }
                }
            }
        } catch (\PDOException $e) {
            self::writeln('Failure');
            self::writeln($e->getMessage());

            return false;
        }

        if ( $seed ) return self::seed();

        return true;
    }

    public static function seed($class = null)
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
}
