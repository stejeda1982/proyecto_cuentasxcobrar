<?php

declare(strict_types=1);

namespace Modules\Seguridad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parámetro de configuración del sistema (T0.2).
 *
 * Los valores se tipan (string|integer|boolean|json) y todo cambio queda
 * auditado con el usuario que lo realizó (RNF-SEG-03).
 */
/**
 * @property int $id
 * @property string $clave
 * @property string|null $valor
 * @property string $tipo
 * @property string|null $descripcion
 * @property int|null $actualizado_por
 */
class Configuracion extends Model
{
    protected $table = 'seguridad_configuracion';

    protected $fillable = ['clave', 'valor', 'tipo', 'descripcion', 'actualizado_por'];

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'actualizado_por');
    }

    /**
     * Valor tipado según la columna tipo.
     */
    public function valorTipado(): string|int|bool|array|null
    {
        return match ($this->tipo) {
            'integer' => (int) $this->valor,
            'boolean' => in_array($this->valor, ['1', 'true', 'verdadero'], true),
            'json' => json_decode((string) $this->valor, true),
            default => $this->valor,
        };
    }
}
