<?php
namespace Clicalmani\Flesco\Http\Requests;

use Clicalmani\Flesco\Support\Log;

class UploadedFile 
{
    /**
     * Controller
     * 
     * @param string $name File key
     */
    public function __construct(private string $name) {}

    /**
     * Get the uploaded file
     * 
     * @return \stdClass
     */
    public function getFile() : \stdClass
    {
        return (object) $_FILES[$this->name];
    }

    /**
     * Get file name
     * 
     * @return string
     */
    public function getName() : string
    {
        return $this->getFile()->name;
    }

    /**
     * Check if file is valid
     * 
     * @return bool
     */
    public function isValid() : bool
    {
        return $this->getFile()->error == FALSE;
    }

    /**
     * Get the stored file extension
     * 
     * @return string
     */
    public function getExtension() : string
    {
        return pathinfo($this->getFile()->tmp_name, PATHINFO_EXTENSION);
    }

    /**
     * Get the original file extension
     * 
     * @return string
     */
    public function getClientOriginalExtension() : string
    {
        return substr($this->getFile()->name, strrpos($this->getFile()->name, '.') + 1);
    }

    /**
     * Move uploaded file from the temp directory
     * 
     * @param ?string $directory Directory to move the file to. If omitted storage/uploads will be used.
     * @param ?string $name New file name. If omitted the uploaded file name will be used.
     * @return bool
     */
    public function move($dir = null, $name = null)  : bool
    {
        if (!$dir) {
            if (FALSE === file_exists(storage_path('/uploads'))) 
                mkdir(storage_path('/uploads'));
            $dir = storage_path('/uploads');
        }

        $name = isset($name) ? $name: $this->getName();
        
        if (is_dir($dir)) {
            if (false == @ $this->getFile()->time) return move_uploaded_file($this->getFile()->tmp_name, $dir . DIRECTORY_SEPARATOR . $name);
            else rename($this->getFile()->tmp_name, $dir . DIRECTORY_SEPARATOR . $name);
        }

        return false;
    }

    /**
     * Add uploaded file
     * 
     * @param string $name File name
     * @param string $path File temp path
     * @param string $size File size
     * @param string $type File mimetype
     * @return void
     */
    public function addFile(string $name, string $path, int $size, string $type) : void
    {
        $_FILES[$this->name] = [
            'name'      => $name,
            'full_path' => $name,
            'type'      => $type,
            'tmp_name'  => $path,
            'error'     => !$size ? 1: 0,
            'size'      => $size,
            'time'      => time()
        ];
    }
}
