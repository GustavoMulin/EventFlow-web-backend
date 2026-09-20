<?php

namespace App\Models;

use Database\Factories\InscricaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $codigo
 * @property int $evento_id
 * @property string $nome
 * @property string $email
 * @property string|null $documento
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nome', 'email', 'documento'])]
class Inscricao extends Model
{
    /** @use HasFactory<InscricaoFactory> */
    use HasFactory;

    protected $table = 'inscricoes';

    protected static function booted(): void
    {
        // Código único do ingresso, gerado automaticamente na criação.
        static::creating(function (Inscricao $inscricao) {
            $inscricao->codigo ??= (string) Str::uuid();
        });
    }

    /**
     * As rotas usam o código (UUID) e não o id, para o ingresso não ser "adivinhável".
     */
    public function getRouteKeyName(): string
    {
        return 'codigo';
    }

    /**
     * @return BelongsTo<Evento, $this>
     */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }
}
