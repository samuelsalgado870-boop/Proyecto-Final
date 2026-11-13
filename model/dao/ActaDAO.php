<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\ActaVO;

final class ActaDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'acta';
    protected const VO = ActaVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'tipo', 'numero', 'fecha', 'version', 'documento_id', 'operacion_tipo', 'operacion_id'];
}
