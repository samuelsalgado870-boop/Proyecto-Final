<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\CategoriaActivoVO;

final class CategoriaActivoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'categoria_activo';
    protected const VO = CategoriaActivoVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'codigo', 'nombre', 'estado'];
}
