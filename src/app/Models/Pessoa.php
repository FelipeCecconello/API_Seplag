<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pessoa extends Model
{
    use HasFactory;

    protected $table = 'pessoa';
    protected $primaryKey = 'pes_id';
    
    protected $fillable = [
        'pes_nome', 'pes_data_nascimento', 'pes_sexo', 'pes_mae', 'pes_pai'
    ];

    public function enderecos()
    {
        return $this->belongsToMany(
            Endereco::class, 
            'pessoa_endereco',      
            'pes_id',                
            'end_id',                  
            'pes_id',                
            'end_id'
        );
    }

    public function fotos()
    {
        return $this->hasMany(
            FotoPessoa::class,
            'pes_id',  
            'pes_id' 
        );
    }
    
    public function servidorTemporario()
    {
        return $this->hasOne(
            ServidorTemporario::class,
            'pes_id',  
            'pes_id' 
        );
    }

    public function servidorEfetivo()
    {
        return $this->hasOne(
            ServidorEfetivo::class,
            'pes_id',  
            'pes_id' 
        );
    }

    public function lotacao()
    {
        return $this->hasOne(
            Lotacao::class,
            'pes_id',  
            'pes_id' 
        );
    }
}