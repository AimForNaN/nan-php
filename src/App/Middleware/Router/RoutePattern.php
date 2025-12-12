<?php

namespace NaN\App\Middleware\Router;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;

class RoutePattern {
	private array $groups = [];
	private bool $has_parameters = false;
	private array $matches = [];
	private string $regex = '';

	public function __construct(
		private string $pattern,
	) {
	}

	public function compile(): string {
		if (!empty($this->regex)) {
			return $this->regex;
		}

		if ($this->has_parameters = static::hasParameters($this->pattern)) {
			if (preg_match_all('#\{([a-zA-Z_]\w+)\}#', $this->pattern, $matches)) {
				[$matches, $groups] = $matches;
				$this->groups = $groups;
				$this->regex = $this->pattern;


				$replacements = [];
				foreach ($groups as $group) {
					$replacements['{' . $group . '}'] = '(?P<' . $group . '>[^/]+)';
				}

				$replacement = \strtr($this->regex, $replacements);
				return $this->regex = "#^{$replacement}$#i";
			}
		}

		return $this->regex = "#^{$this->pattern}$#i";
	}

	public function getGroups(): array {
		return $this->groups;
	}

	public function getMatches(): array {
		return $this->matches;
	}

	static public function hasParameters(string $pattern): bool {
		return \str_contains($pattern, '{');
	}

	public function matches(string $path): bool {
		$this->matches = [];

		// Static match!
		if ($this->pattern === $path) {
			return true;
		}

		if (!$this->has_parameters) {
			return false;
		}

		$ret = \preg_match($this->regex, $path, $matches);

		if (\count($matches)) {
			foreach ($this->groups as $name) {
				$this->matches[$name] = $matches[$name];
			}
		}

		return (bool)$ret;
	}

	public function matchesRequest(PsrServerRequestInterface $request): bool {
		$path = $request->getUri()->getPath();
		return $this->matches($path);
	}
}
