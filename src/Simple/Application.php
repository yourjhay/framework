<?php declare(strict_types=1);

namespace Simple;

use Dotenv\Dotenv;

class Application
{
    public function boot(?string $configDir = null): void
    {
        $this->loadEnvironment();
        Config::load($configDir);
        $this->initSession();
        $this->setErrorHandler();
    }

    protected function loadEnvironment(): void
    {
        $envPath = $this->findEnvPath();

        if ($envPath !== null && file_exists($envPath . '/.env')) {
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->safeLoad();
        }
    }

    protected function findEnvPath(): ?string
    {
        // Check CWD first (works for most containerized setups)
        if (file_exists(getcwd() . '/.env')) {
            return getcwd();
        }

        // Walk up from src/Simple/ to find project root
        $dir = __DIR__;
        for ($i = 0; $i < 5; $i++) {
            $dir = dirname($dir);
            if (file_exists($dir . '/.env')) {
                return $dir;
            }
        }

        return null;
    }

    protected function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                ini_set('session.cookie_secure', 1);
            }
            session_start();
        }
    }

    protected function setErrorHandler(): void
    {
        error_reporting(E_ALL);
        $handler = Config::get('security.error_handler', 'simply');

        if ($handler === 'simply' || $handler === 'none') {
            set_error_handler('Simple\Error::errorHandler');
            set_exception_handler('Simple\Error::exceptionHandler');
        } elseif ($handler === 'whoops') {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            $whoops->register();
        } else {
            set_error_handler('Simple\Error::errorHandler');
            set_exception_handler('Simple\Error::exceptionHandler');
        }
    }
}
