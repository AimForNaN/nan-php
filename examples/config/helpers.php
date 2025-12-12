<?php

use NaN\App;
use NaN\App\TemplateEngine;
use NaN\Database\{
	Drivers\SqlDriver,
	Query\Builders\SqlQueryBuilder,
};
use NaN\Env;

function app(): App {
	static $app = null;

	if (!$app) {
		$services = include(__DIR__ . '/services.php');
		$router = include(__DIR__ . '/routes.php');
		$app = new App($services);

		$app->use($router);
	}

	return $app;
}

function db(): SqlQueryBuilder {
	static $db = null;

	if (!$db) {
		$db = new SqlDriver([
			'driver' => 'sqlite',
			'sqlite' => ':memory:',
		]);
	}

	return $db->createConnection();
}

function env(string $key, mixed $fallback = null): ?string {
	if (!Env::isLoaded()) {
		Env::load();
	}

	return Env::get($key, $fallback);
}

function dbg(mixed $msg): void {
	NaN\Debug::log($msg);
}

function tpl(): TemplateEngine {
	static $tpl = null;

	if (!$tpl) {
		$tpl = new TemplateEngine($_SERVER['DOCUMENT_ROOT'] . '/views/', 'tpl.php');
	}

	return $tpl;
}
