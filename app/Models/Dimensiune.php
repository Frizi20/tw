<?php

namespace App\Models;

use \DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dimensiune extends Model
{
    use SoftDeletes;
    use HasFactory;

    public $table = 'dimensiunes';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'dimensiune',
        'departament_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function departament()
    {
        return $this->belongsTo(Departamente::class, 'departament_id');
    }

    public function departaments()
    {
        return $this->belongsToMany(Departamente::class,'departamentes_dimensiunes','dimensiune_id','departament_id');
    }

    // > Departamente::find(3)->dimensions[0]->categoriiDeControl
    public function categoriiDeControl()
    {
        return $this->belongsToMany(CategorieDeControl::class,'survey_builders','dimensiune_id','categorie_de_control_id')->whereNull('survey_builders.deleted_at')->withPivot('departamente_id');
    }

    public function withoutCatogoriiDeControl()
    {
        return $this->belongsToMany(CategorieDeControl::class,'survey_builders','dimensiune_id','categorie_de_control_id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
