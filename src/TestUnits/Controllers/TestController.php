<?php 
namespace Clicalmani\Flesco\TestUnits\Controllers;

use Clicalmani\Database\Factory\Sequence;
use Clicalmani\Flesco\Http\Requests\Request;
use Clicalmani\Flesco\TestUnits\TestCase;

abstract class TestController extends TestCase
{
    /**
     * User model class
     * 
     * @var \Clicalmani\Flesco\Models\Model Object
     */
    protected $controller;

    private $action, 
            $override = [], 
            $user, 
            $counter = 1;

    /**
     * Merges parameters
     * 
     * @param array $parameters [Optional]
     * @return array 
     */
    private function merge(array $parameters = []) : array
    {
        return array_merge($this->override, $parameters);
    }

    /**
     * Override parameters
     * 
     * @param array $parameters Only specified parameters will be overriden
     * @return array New seed
     */
    private function override($parameters = [])
    {
        $this->override = $this->merge($parameters);
        $parameters = $this->{$this->action}();
        
        foreach ($this->override as $attribute => $value) {
            $parameters[$attribute] = $value;
        }

        return $parameters;
        return with( new \Clicalmani\Flesco\Http\Controllers\RequestController )
                ->invokeControllerMethod($this->controller, $this->action);
    }

    /**
     * Create a new test
     * 
     * @param string $action Action method
     * @return static
     */
    public function new(string $action) : static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Manipulate factory states
     * 
     * @param callable $callback A callable function that receive default attributes and return the 
     * attributes to override.
     * @return static
     */
    public function state(?callable $callback) : static
    {
        $this->override( $callback( $this->{$this->action}() ) );
        return $this;
    }

    /**
     * Request user
     * 
     * @param callable $param A callable function to return the request user id or an integer value.
     * @return static
     */
    public function user(Sequence|int $param) : static
    {
        if ( is_int($param) ) $this->user = (int) $param;
        elseif ( $param instanceof Sequence ) $this->user = $param;
        return $this;
    }

    /**
     * Repeat the seed operation n times.
     * 
     * @param int $num Counter
     * @return $this
     */
    public function count($num = 1) : static
    {
        $this->counter = $num;
        return $this;
    }

    /**
     * Make test
     * 
     * @return void
     */
    public function make($attributes = []) : void
    {
        foreach (range(1, $this->counter) as $num) {
            $request = new Request;
            $parameters = $this->override($attributes);

            /**
             * Parameter sequence
             */
            foreach ($parameters as $key => $param) {
                if ($param instanceof Sequence) $parameters[$key] = call( $param );
            }

            $request->make( $parameters );
            
            /**
             * User sequence
             */
            if ($this->user) {
                if ($this->user instanceof Sequence) {
                    $request->test_user_id = call( $this->user );
                } else $request->test_user_id = $this->user;
            }

            echo with( new $this->controller )
                    ->invokeControllerMethod($this->controller, $this->action);
            if ($num < $this->counter) echo "\n";
        }
    }
}
