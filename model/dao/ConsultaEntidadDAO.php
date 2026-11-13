<?php

declare(strict_types=1);

namespace App\Model\DAO;

use DateTimeImmutable;
use PDO;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Base de consultas de solo lectura. Los nombres de tabla y columna proceden
 * exclusivamente de constantes declaradas en cada DAO concreto.
 */
abstract class ConsultaEntidadDAO extends AbstractDAO
{
    protected const TABLE = '';
    protected const VO = '';
    protected const PRIMARY_KEY = [];
    protected const COLUMNS = [];
    protected const SEARCHABLE_COLUMNS = [];

    public function buscarPorId(int $id): ?object
    {
        if (count(static::PRIMARY_KEY) !== 1) {
            throw new \LogicException('Esta entidad tiene clave primaria compuesta; use buscarPorClave().');
        }
        return $this->buscarPorClave([static::PRIMARY_KEY[0] => $id]);
    }

    /** @param array<string, int|string> $clave */
    public function buscarPorClave(array $clave): ?object
    {
        if (array_diff(array_keys($clave), static::PRIMARY_KEY) !== []
            || array_diff(static::PRIMARY_KEY, array_keys($clave)) !== []) {
            throw new \InvalidArgumentException('La clave debe incluir exactamente las columnas de la PK.');
        }
        $conditions = [];
        $params = [];
        foreach (static::PRIMARY_KEY as $column) {
            $placeholder = ':pk_' . $column;
            $conditions[] = $column . ' = ' . $placeholder;
            $params[$placeholder] = $clave[$column];
        }
        $stmt = $this->pdo->prepare('SELECT * FROM ' . static::TABLE . ' WHERE ' . implode(' AND ', $conditions) . ' LIMIT 1');
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hidratar($row);
    }

    /** @return list<object> */
    public function buscarPorCampo(string $campo, int|string|null $valor, int $limite = 100): array
    {
        $searchable = static::SEARCHABLE_COLUMNS === [] ? static::COLUMNS : static::SEARCHABLE_COLUMNS;
        if (!in_array($campo, $searchable, true)) {
            throw new \InvalidArgumentException('La columna solicitada no pertenece a esta entidad.');
        }
        if ($limite < 1 || $limite > 500) {
            throw new \InvalidArgumentException('El límite debe estar entre 1 y 500.');
        }
        $where = $valor === null ? $campo . ' IS NULL' : $campo . ' = :valor';
        $stmt = $this->pdo->prepare('SELECT * FROM ' . static::TABLE . ' WHERE ' . $where . ' ORDER BY ' . static::PRIMARY_KEY[0] . ' LIMIT :limite');
        if ($valor !== null) {
            $stmt->bindValue(':valor', $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn (array $row): object => $this->hidratar($row), $stmt->fetchAll());
    }

    protected function hidratar(array $row): object
    {
        $reflection = new ReflectionClass(static::VO);
        $arguments = [];
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();
            if (!array_key_exists($name, $row)) {
                throw new \UnexpectedValueException('Falta la columna ' . $name . ' para hidratar ' . static::VO . '.');
            }
            $value = $row[$name];
            if ($value === null) {
                $arguments[] = null;
                continue;
            }
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                if ($type->getName() === DateTimeImmutable::class) {
                    $value = new DateTimeImmutable((string) $value);
                }
            } elseif ($type instanceof ReflectionNamedType) {
                $value = match ($type->getName()) {
                    'int' => (int) $value,
                    'bool' => (bool) $value,
                    'string' => (string) $value,
                    default => $value,
                };
            }
            $arguments[] = $value;
        }
        return $reflection->newInstanceArgs($arguments);
    }
}
