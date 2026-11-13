<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\AprobacionVO;

final class AprobacionDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'aprobacion';
    protected const VO = AprobacionVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'operacion_tipo', 'operacion_id', 'aprobador_id', 'decision', 'fecha', 'observacion'];
}
