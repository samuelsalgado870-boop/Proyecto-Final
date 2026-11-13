<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\RolVO;

final class RolDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'rol';
    protected const VO = RolVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'nombre', 'descripcion', 'estado'];
}
