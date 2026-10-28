<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
     protected $fillable = [
        'adresse', 'montant', 'statut',
        'livreur_id', 'livree_le', 'annulee_le',
    ];

    protected $casts = [
        'livree_le' => 'datetime',
        'annulee_le' => 'datetime',
    ];

    public function livreur()
    {
        return $this->belongsTo(Livreur::class);
    }
}
