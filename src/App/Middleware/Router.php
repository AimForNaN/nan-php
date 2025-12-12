<?php

namespace NaN\App\Middleware;

use NaN\App;
use NaN\App\Middleware\Router\Route;
use Psr\Http\Message\{
	ResponseInterface as PsrResponseInterface,
	ServerRequestInterface as PsrServerRequestInterface,
};
use Psr\Http\Server\{
	MiddlewareInterface as PsrMiddlewareInterface,
	RequestHandlerInterface as PsrRequestHandlerInterface,
};

class Router implements \ArrayAccess, PsrMiddlewareInterface {
	public function __construct(
		protected Route $root = new Route('/'),
	) {
	}

	public function insert(string $path, mixed $handler): Route {
		$parts = $this->parsePath($path);
		$current = $this->root;
		$route_path = null;

		foreach ($parts as $part) {
			$route_path .= '/' . $part;

			if (!isset($current[$part])) {
				$route = new Route($route_path);
				$current[$part] = $route;
			}

			$current = $current[$part];
		}

		$current->handler = $handler;

		return $current;
	}

	public function match(string $path): ?Route {
		$parts = $this->parsePath($path);

		$parent = $this->root;
		foreach ($parts as $part) {
			$match = $parent->match($part);

			if (!$match) {
				return null;
			}

			$parent = $match;
		}

		return $parent;
	}

	public function offsetExists(mixed $offset): bool {
		return (bool)$this->match($offset);
	}

	public function offsetGet(mixed $offset): ?Route {
		return $this->match($offset);
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		$this->insert($offset, $value);
	}

	public function offsetUnset(mixed $offset): void {
	}

	protected function parsePath(string $path): array {
		return \array_filter(\explode('/', $path));
	}

	public function process(
		PsrServerRequestInterface $request,
		PsrRequestHandlerInterface $handler,
		?App $app = null,
	): PsrResponseInterface {
		$route = $this->match($request->getUri()->getPath());

		if (!$route) {
			return $handler->handle($request, $app);
		}

		return $route->handle($request, $app);
	}
}
