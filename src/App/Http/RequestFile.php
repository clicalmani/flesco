<?php
namespace Clicalmani\Flesco\App\Http;

class RequestFile {

    private $name;

    public function __construct($name) {
        $this->name = $name;
    }

    public function getFile() {
        return (object) $_FILES[$this->name];
    }

    public function getName() {
        return $this->getFile()->name;
    }

    public function isValid() {
        return $this->getFile()->error == false;
    }

    public function getExtension() {
        return pathinfo($this->getFile()->tmp_name, PATHINFO_EXTENSION);
    }

    public function getClientOriginalExtension() {
        return substr($this->getFile()->name, strrpos($this->getFile()->name, '.')+1);
    }

    public function move($dir, $name) {
        if (is_dir($dir)) {
            return move_uploaded_file($this->getFile()->tmp_name, $dir . DIRECTORY_SEPARATOR . $name);
        }

        return false;
    }
}