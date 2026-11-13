<?php

declare(strict_types=1);

namespace Modules\Seguridad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $nombre
 * @property string|null $descripcion
 */
class Rol extends Model
{
    protected $table = 'seguridad_roles';

    protected $fillable = ['slug', 'nombre', 'descripcion'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }
}
