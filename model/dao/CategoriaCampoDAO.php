<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\CategoriaCampoVO;

final class CategoriaCampoDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'categoria_campo';
    protected const VO = CategoriaCampoVO::class;
    protected const PRIMARY_KEY = ['categoria_id', 'campo_codigo'];
    protected const COLUMNS = ['categoria_id', 'campo_codigo', 'obligatorio'];
}
