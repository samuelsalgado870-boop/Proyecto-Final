<?php

declare(strict_types=1);

namespace App\Model\DAO;

use App\Model\VO\InventarioDetalleVO;

final class InventarioDetalleDAO extends ConsultaEntidadDAO
{
    protected const TABLE = 'inventario_detalle';
    protected const VO = InventarioDetalleVO::class;
    protected const PRIMARY_KEY = ['id'];
    protected const COLUMNS = ['id', 'inventario_id', 'activo_id', 'verificador_id', 'resultado', 'placa_observada', 'ubicacion_observada', 'condicion_fisica', 'fecha_verificacion', 'observacion'];
}
