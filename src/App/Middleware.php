<?php

namespace NaN\App;

use NaN\App;
use NaN\DI\Exceptions\NotFoundException;
use NaN\Http\Response;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\{
	ResponseInterface as PsrResponseInterface,
	ServerRequestInterface as PsrServerRequestInterface,
};
use Psr\Http\Server\{
	MiddlewareInterface as PsrMiddlewareInterface,
	RequestHandlerInterface as PsrRequestHandlerInterface,
};

class Middleware implements \ArrayAccess, \Iterator, PsrContainerInterface, PsrMiddlewareInterface, PsrRequestHandlerInterface {
	public function __construct(
		protected array $children = [],
	) {
	}

	public function current(): mixed {
		return \current($this->children);
	}

	public function get(string $id): mixed {
		if (isset($this->children[$id])) {
			return $this->children[$id];
		}

		foreach ($this->children as $child) {
			if (\is_a($child, $id)) {
				return $child;
			}
		}

		throw new NotFoundException("Middleware {$id} not found!");
	}

	public function handle(PsrServerRequestInterface $request, ?App $app = null): PsrResponseInterface {
		return $this->process($request, $this, $app);
	}

	public function has(string $id): bool {
		if (isset($this->children[$id])) {
			return true;
		}

		foreach ($this->children as $child) {
			if (\is_a($child, $id)) {
				return true;
			}
		}

		return false;
	}

	public function key(): mixed {
		return \key($this->children);
	}

	public function next(): void {
		\next($this->children);
	}

	public function offsetExists(mixed $offset): bool {
		return $this->has($offset);
	}

	public function offsetGet(mixed $offset): mixed {
		return $this->get($offset);
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		if (!\is_null($offset)) {
			$this->children[] = $value;
		} else {
			$this->children[$offset] = $value;
		}
	}

	public function offsetUnset(mixed $offset): void {
		unset($this->children[$offset]);
	}

	public function process(
		PsrServerRequestInterface $request,
		PsrRequestHandlerInterface $handler,
		?App $app = null,
	): PsrResponseInterface {
		if (!$this->valid()) {
			return new Response(404);
		}

		$middleware = $this->current();
		$this->next();

		return $middleware->process($request, $handler, $app);
	}

	public function rewind(): void {
		\reset($this->children);
	}

	public function valid(): bool {
		return (bool)\current($this->children);
	}
}
