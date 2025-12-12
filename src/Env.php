<?php

namespace NaN;

/**
 * Manange environment variables.
 */
final class Env {
	static protected array $aliases = [];
	static protected array $env = [];

	private function __construct() {}

	/**
	 * Get environment variable.
	 *
	 * @param string $key Environment variable key.
	 * @param string|null $fallback Fallback value (defaults to null).
	 *
	 * @return ?string Environment variable value or fallback.
	 */
	static public function get(string $key, ?string $fallback = null): ?string {
		$key = Env::$aliases[$key] ?? $key;
		return Env::$env[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? $fallback;
	}

	static public function isLoaded(): bool {
		return !empty(Env::$env);
	}

	/**
	 * Load variables from `.env` file.
	 *
	 * Can only be run once per session.
	 */
	static public function load(?string $dir = null): void {
		if (!Env::isLoaded()) {
			$env = \Dotenv\Dotenv::createImmutable($dir ?? $_SERVER['DOCUMENT_ROOT']);
			Env::$env = $env->safeLoad();
		}
	}

	/**
	 * Register an alias key for an environment variable.
	 *
	 * @param string $alias Alias key.
	 * @param string $original Original key.
	 */
	static public function registerAlias(string $alias, string $original): void {
		Env::$aliases[$alias] = $original;
	}
}

