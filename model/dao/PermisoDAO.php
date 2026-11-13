<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\PermisoVO;

final class PermisoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'permiso';
    protected const VO = PermisoVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'modulo', 'recurso', 'accion'];
}
