<?php
namespace Clicalmani\Flesco\Http\Requests;

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

    public function move($dir = null, $name = null) {
        $dir = isset($dir) ? $dir: storage_path('/uploads');
        $name = isset($name) ? $name: $this->getName();
        if (is_dir($dir)) {
            if (false == $this->getFile($name)->uploaded) return move_uploaded_file($this->getFile()->tmp_name, $dir . DIRECTORY_SEPARATOR . $name);
            else return rename(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->getFile()->tmp_name, $dir . DIRECTORY_SEPARATOR . $name);
        }

        return false;
    }
}