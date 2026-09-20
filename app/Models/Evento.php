<?php

namespace App\Models;

use Database\Factories\EventoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nome
 * @property string $descricao
 * @property Carbon $data_evento
 * @property string $hora_evento
 * @property string $preco
 * @property string|null $endereco
 * @property int $vagas
 * @property string|null $banner
 * @property int $categoria_id
 * @property int $local_id
 * @property int|null $inscricoes_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'nome', 'descricao', 'data_evento', 'hora_evento', 'preco', 'endereco',
    'vagas', 'banner', 'categoria_id', 'local_id',
])]
class Evento extends Model
{
    /** @use HasFactory<EventoFactory> */
    use HasFactory;

    protected $table = 'eventos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_evento' => 'date',
            'preco' => 'decimal:2',
            'vagas' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * @return BelongsTo<Local, $this>
     */
    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    /**
     * @return HasMany<Inscricao, $this>
     */
    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class);
    }

    /**
     * Vagas ainda disponíveis (usa o withCount('inscricoes') quando carregado).
     */
    public function vagasRestantes(): int
    {
        $inscritos = $this->inscricoes_count ?? $this->inscricoes()->count();

        return max(0, $this->vagas - $inscritos);
    }
}
