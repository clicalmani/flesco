<?php 
namespace Clicalmani\Flesco\Providers;

abstract class TPS 
{
    /**
     * Current route
     * 
     * @var string
     */
    protected $route;

    /**
     * Request object
     * 
     * @var \Clicalmani\Flesco\Http\Requests\Request
     */
    protected $request;

    public function __construct()
    {
        $this->request = new \Clicalmani\Flesco\Http\Requests\Request;
    }

    /**
     * Abort request
     * 
     * @return void
     */
    public function abort() : void
    {
        $this->route = false;
    }

    /**
     * Get route parameters
     * 
     * @return array
     */
    public function getParams() : array
    {
        preg_match_all('/:[^\/]+/', (string) $this->route, $mathes);

        $parameters = [];
        
        if ( count($mathes) ) {

            $mathes = $mathes[0];
            
            foreach ($mathes as $name) {
                $name = substr($name, 1);    				      // Remove starting two dots (:)
                
                if (preg_match('/@/', $name)) {
                    $name = substr($name, 0, strpos($name, '@')); // Remove validation part
                }
                
                $parameters[] = $name;
            }
        }

        return $parameters;
    }

    /**
     * @override
     */
    abstract public function redirect();
}
