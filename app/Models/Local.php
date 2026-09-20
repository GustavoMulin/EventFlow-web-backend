<?php

namespace App\Models;

use Database\Factories\LocalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nome
 * @property float $latitude
 * @property float $longitude
 * @property string|null $endereco
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nome', 'latitude', 'longitude', 'endereco'])]
class Local extends Model
{
    /** @use HasFactory<LocalFactory> */
    use HasFactory;

    protected $table = 'locais';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * @return HasMany<Evento, $this>
     */
    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class, 'local_id');
    }
}
