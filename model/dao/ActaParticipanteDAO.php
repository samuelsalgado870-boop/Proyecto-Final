<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\ActaParticipanteVO;

final class ActaParticipanteDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'acta_participante';
    protected const VO = ActaParticipanteVO::class;
    protected const PRIMARY_KEY = ['acta_id', 'usuario_id'];
    protected const COLUMNS = ['acta_id', 'usuario_id', 'papel'];
}
