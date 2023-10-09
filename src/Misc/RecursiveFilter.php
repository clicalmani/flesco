<?php 
namespace Clicalmani\Flesco\Misc;

class RecursiveFilter extends \RecursiveFilterIterator
{
    private $filters = [];

    public function accept(): bool
    {
        if ($this->current()->isDir()) {
            if (in_array($this->current()->getFileName(), $this->filters)) return true;
            else return false;
        }

        return true;
    }

    public function getFiles()
    {
        $files = [];

        foreach (new \RecursiveIteratorIterator($this) as $file) { 
            $pathname = $file->getPathname();

                if($file->isFile()) {
                    $filename = $file->getFileName(); 

                    if(is_readable($pathname)) {
                        $files[$filename] = $pathname;
                    }
                }
        }

        asort($files);

        return $files;
    }
}
