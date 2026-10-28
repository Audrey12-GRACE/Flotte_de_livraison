<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Livreur extends Model
{
    protected $fillable = ['nom', 'telephone', 'vehicule', 'zone'];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
