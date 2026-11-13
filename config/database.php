<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

final class Database
{
    public function connect(): PDO
    {
        $environment = strtolower(trim($this->env('APP_ENV', 'local')));
        if (!in_array($environment, ['local', 'development', 'test', 'production', 'prod'], true)) {
            throw new \InvalidArgumentException('APP_ENV debe ser local, development, test o production.');
        }
        $isProduction = in_array($environment, ['production', 'prod'], true);

        $host = $this->env('DB_HOST', '127.0.0.1');
        $port = $this->env('DB_PORT', '3306');
        $database = $this->env('DB_NAME', 'gestion_activos');
        $username = $this->env('DB_USER', $isProduction ? '' : 'root');
        $password = $this->env('DB_PASSWORD', '');
        $charset = $this->env('DB_CHARSET', 'utf8mb4');

        if ($isProduction) {
            foreach (['DB_HOST' => $host, 'DB_NAME' => $database, 'DB_USER' => $username, 'DB_PASSWORD' => $password] as $name => $value) {
                if (getenv($name) === false || trim($value) === '') {
                    throw new \InvalidArgumentException("{$name} debe configurarse explícitamente en producción.");
                }
            }
            if (strtolower($username) === 'root') {
                throw new \InvalidArgumentException('Producción debe usar una cuenta de base de datos con privilegios mínimos, no root.');
            }
        }

        if ($host === '' || str_contains($host, ';')) {
            throw new \InvalidArgumentException('DB_HOST no es válido.');
        }
        if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            throw new \InvalidArgumentException('DB_PORT debe ser un puerto válido.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
            throw new \InvalidArgumentException('DB_NAME contiene caracteres no permitidos.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $charset)) {
            throw new \InvalidArgumentException('DB_CHARSET contiene caracteres no permitidos.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        } catch (PDOException $exception) {
            throw new PDOException('No fue posible conectar con la base de datos configurada.', (int) $exception->getCode(), $exception);
        }
    }

    private function env(string $name, string $default): string
    {
        $value = getenv($name);
        return $value === false ? $default : $value;
    }
}
