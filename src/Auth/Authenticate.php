<?php
namespace Clicalmani\Flesco\Auth;

use Clicalmani\Flesco\Models\Model;
use Clicalmani\Flesco\Providers\ServiceProvider;

abstract class Authenticate extends ServiceProvider implements \ArrayAccess , \JsonSerializable
{
	/**
	 * User Model
	 * 
	 * @var string
	 */
	protected $userModel;

	/**
	 * Serializer
	 * 
	 * @var callable
	 */
	protected $serializer;

	/**
	 * Authenticated user
	 * 
	 * @var \App\Models\User
	 */
	protected $user;
	 
	/**
	 * Constructor
	 *
	 * @param mixed $user_id 
	 */
	public function __construct(protected mixed $user_id = NULL)
	{
		$this->user = instance($this->userModel, fn(Model $instance) => $instance, $user_id);
	}

	/**
	 * User data serializer
	 * 
	 * @param callable $callback
	 * @return void
	 */
	protected function serialize(callable $callback) : void
	{
		$this->serializer = $callback;
	}

	public function jsonSerialize(): mixed
	{
		if ($this->serializer) return call($this->serializer);

		return null;
	}
	
	/**
	 * @override
	 * 
	 * @param string $attribute
	 * @return mixed
	 */
	public function __get(string $attribute)
	{
		return $this->user?->{$attribute};
	}

	public function __toString()
	{
		if ($this->serializer) return call($this->serializer);

		return null;
	}
}
