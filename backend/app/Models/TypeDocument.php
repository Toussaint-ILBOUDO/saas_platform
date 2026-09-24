<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeDocument extends Model
{
    use SoftDeletes;

    protected $table = 'type_documents';

    protected $fillable = ['nom', 'sigle'];

    protected function casts(): array
    {
        return [];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentBibliotheque::class, 'type_document_id');
    }
}
