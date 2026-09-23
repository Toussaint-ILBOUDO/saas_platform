<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTag extends Model
{
    protected $fillable = ['document_bibliotheque_id', 'tag_id'];

    public function document()
    {
        return $this->belongsTo(DocumentBibliotheque::class, 'document_bibliotheque_id');
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}
