<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\AmbitoAccesoVO;

final class AmbitoAccesoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'ambito_acceso';
    protected const VO = AmbitoAccesoVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'usuario_id', 'tipo_ambito', 'sede_id', 'dependencia_id', 'ubicacion_id', 'vigente_desde', 'vigente_hasta'];
}
