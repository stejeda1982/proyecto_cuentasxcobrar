<?php

declare(strict_types=1);

namespace Modules\Seguridad\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registro de auditoría (T0.5 — RNF-SEG-03).
 *
 * Tabla de solo inserción: el modelo no expone actualizaciones ni borrados
 * y el Observer registra cada evento de los modelos auditables con los
 * valores antes/después.
 */
/**
 * @property int $id
 * @property int|null $usuario_id
 * @property string|null $usuario_texto
 * @property string $evento
 * @property string $auditable_type
 * @property string $auditable_id
 * @property array|null $valores_anteriores
 * @property array|null $valores_nuevos
 * @property string|null $ip
 * @property string|null $user_agent
 * @property CarbonInterface|null $creado_en
 */
class Auditoria extends Model
{
    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = null;

    protected $table = 'seguridad_auditoria';

    protected $fillable = [
        'usuario_id', 'usuario_texto', 'evento', 'auditable_type',
        'auditable_id', 'valores_anteriores', 'valores_nuevos',
        'ip', 'user_agent', 'creado_en',
    ];

    protected function casts(): array
    {
        return [
            'valores_anteriores' => 'array',
            'valores_nuevos' => 'array',
            'creado_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
