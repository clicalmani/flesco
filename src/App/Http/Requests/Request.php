<?php
namespace Clicalmani\Flesco\App\Http\Requests;

use Clicalmani\Flesco\App\Http\Controllers\RequestController;
use Clicalmani\Flesco\App\Http\Requests\RequestFile;
use Clicalmani\Flesco\App\Http\Requests\RequestRedirect;
use Clicalmani\Flesco\Security\Security;

class Request implements \ArrayAccess {

    private $signatures;

    public function __construct( $signatures = [] ) {
        $this->signatures = $signatures;
    }

    public function __get($property) 
    {
        $query_array = explode( '&', parse_url(
            $_SERVER['REQUEST_URI'],
            PHP_URL_QUERY
        ) );

        $query = [];

        foreach ( $query_array as $str ) {
            $a = explode( '=', $str );
            $query[ $a[0] ] = $a[1];
        }

        $sanitized = Security::sanitizeVars($query, $this->signatures);
        
		if ( isset($sanitized[$property])) {
			return $sanitized[$property];
		}

		return null;
    }

    public function hasFile($name) {
        return isset($_FILES[$name]);
    }

    public function file($name) {
        if ( $this->hasFile($name) ) {
            return new RequestFile($name);
        }

        return null;
    }

    public function offsetExists( $property ) {
        return ! is_null($this->$property);
    }

    public function offsetGet( $property ) {
        return $this->$property;
    }

    public function offsetSet( $property, $value ) {
        $this->$property = $value;
    }

    public function offsetUnset( $property ) {
        if ($this->$property) {
            $this->$property = null;
        }
    }

    public function redirect() {
        return new RequestRedirect;
    }

    public function download($filename, $filepath) 
    {
        header('Content-Type: ' . mime_content_type($filepath));
        header("Content-Disposition: attachment; filename=$filename");
        return readfile($filepath);
    }

    public function merge($new_signatures = [])
    {
        $this->signatures = array_merge($this->signatures, $new_signatures);
    }

    public function user()
    {
        $matricule = isset($_SESSION['pdmsid'])? Securite::decrypter($_SESSION['pdmsid']): (isset($_COOKIE['pdmsid'])? Securite::decrypter($_COOKIE['pdmsid']): null);

        $req = new \Users\UsineCompte($matricule);
        $compte = $req->creer();
        return (object) $compte->info();
    }
}