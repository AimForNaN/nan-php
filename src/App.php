<?php

namespace NaN;

use NaN\App\Middleware;
use NaN\DI\Container;
use NaN\DI\Interfaces\ContainerInterface;
use NaN\Http\{
    Request,
    Response,
};
use Psr\Container\{
	ContainerExceptionInterface,
	ContainerInterface as PsrContainerInterface,
	NotFoundExceptionInterface,
};
use Psr\Http\Message\{
	ResponseInterface as PsrResponseInterface,
	ServerRequestInterface as PsrServerRequestInterface,
};
use Psr\Http\Server\{
	MiddlewareInterface as PsrMiddlewareInterface,
	RequestHandlerInterface as PsrRequestHandlerInterface,
};

class App implements \ArrayAccess, PsrContainerInterface, PsrRequestHandlerInterface {
	public function __construct(
		protected PsrContainerInterface $services = new Container(),
		protected PsrRequestHandlerInterface $middleware = new Middleware(),
	) {
		if ($this->services instanceof ContainerInterface
		 && $this->middleware instanceof PsrContainerInterface) {
			$this->services->addDelegate($this->middleware);
		}
	}

	public function get(string $id) {
		return $this->services->get($id);
	}

	public function handle(PsrServerRequestInterface $request): PsrResponseInterface {
		if ($this->middleware instanceof \Iterator) {
			$this->middleware->rewind();
		}

		return $this->middleware->handle($request, $this);
	}

	public function has(string $id): bool {
		return $this->services->has($id);
	}

	public function offsetExists(mixed $offset): bool {
		return $this->services->has($offset);
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function offsetGet(mixed $offset): mixed {
		return $this->services->get($offset);
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		if ($this->services instanceof \ArrayAccess) {
			$this->services->offsetSet($offset, $value);
		} else {
			\trigger_error('Could not register service!', E_USER_WARNING);
		}
	}

	public function offsetUnset(mixed $offset): void {
		if ($this->services instanceof \ArrayAccess) {
			$this->services->offsetUnset($offset);
		}
	}

	/**
	 * Exceptions and errors should be handled on a global level
	 *  (e.g. register_shutdown_function, set_error_handler, set_exception_handler, etc).
	 */
	public function run(): void {
		$req = Request::fromGlobals();
		$rsp = $this->handle($req);
		Response::send($rsp);
	}

	public function use(PsrMiddlewareInterface $middleware): void {
		if ($this->middleware instanceof \ArrayAccess) {
			$this->middleware->offsetSet($middleware::class, $middleware);
		} else {
			\trigger_error('Could not register middleware!', E_USER_WARNING);
		}
	}

	public function withMiddleware(PsrRequestHandlerInterface $middleware): App {
		$new = clone $this;
		$new->middleware = $middleware;
		return $new;
	}

	public function withServices(PsrContainerInterface $container): App {
		$new = clone $this;
		$new->services = $container;
		return $new;
	}
}
