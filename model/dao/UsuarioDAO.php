<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\UsuarioVO;
use DateTimeImmutable;
use PDO;

final class UsuarioDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'usuario';
    protected const VO = UsuarioVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'identificacion', 'nombre', 'correo', 'password_hash', 'estado', 'ultimo_acceso', 'creado_en'];
    protected const SEARCHABLE_COLUMNS = ['id', 'identificacion', 'nombre', 'correo', 'estado'];

    public function crear(string $identificacion, string $nombre, string $correo, string $contrasena): int
    {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        if ($hash === false) {          
            throw new \RuntimeException('No fue posible proteger la contraseña.');
        }   

        $stmt = $this->pdo->prepare(
            'INSERT INTO usuario (identificacion, nombre, correo, password_hash) VALUES (:identificacion, :nombre, :correo, :password_hash)'
        );
        $stmt->execute([
            'identificacion' => $identificacion,
            'nombre' => $nombre,
            'correo' => $correo,
            'password_hash' => $hash,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function buscarPorId(int $id): ?UsuarioVO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuario WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hidratar($row);
    }

    /** @return list<UsuarioVO> */
    public function listar(?string $estado = null, ?string $termino = null, int $limite = 100): array
    {
        if ($limite < 1 || $limite > 500) {
            throw new \InvalidArgumentException('El límite debe estar entre 1 y 500.');
        }
        $where = [];
        $params = [];
        if ($estado !== null) {
            $where[] = 'estado = :estado';
            $params['estado'] = $estado;
        }
        if ($termino !== null && $termino !== '') {
            $where[] = '(nombre LIKE :termino_nombre OR correo LIKE :termino_correo OR identificacion LIKE :termino_identificacion)';
            $like = '%' . $termino . '%';
            $params['termino_nombre'] = $like;
            $params['termino_correo'] = $like;
            $params['termino_identificacion'] = $like;
        }
        $sql = 'SELECT * FROM usuario' . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where)) . ' ORDER BY id LIMIT :limite';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn (array $row): UsuarioVO => $this->hidratar($row), $stmt->fetchAll());
    }

    public function autenticar(string $correo, string $contrasena): ?UsuarioVO
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuario WHERE correo = :correo AND estado = 'ACTIVO' LIMIT 1");
        $stmt->execute(['correo' => $correo]);
        $row = $stmt->fetch();
        if ($row === false || !password_verify($contrasena, $row['password_hash'])) {
            return null;
        }

        $update = $this->pdo->prepare('UPDATE usuario SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute(['id' => (int) $row['id']]);
        $row['ultimo_acceso'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        return $this->hidratar($row);
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        $stmt = $this->pdo->prepare('UPDATE usuario SET estado = :estado WHERE id = :id');
        $stmt->execute(['id' => $id, 'estado' => $estado]);
        return $stmt->rowCount() > 0;
    }

    protected function hidratar(array $row): UsuarioVO
    {
        return new UsuarioVO(
            (int) $row['id'],
            $row['identificacion'],
            $row['nombre'],
            $row['correo'],
            $row['password_hash'],
            $row['estado'],
            $this->date($row['ultimo_acceso']),
            new DateTimeImmutable($row['creado_en'])
        );
    }
}
