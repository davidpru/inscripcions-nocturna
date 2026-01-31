<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Administrador extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'administradores';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'email',
        'password',
        'tipo',
        'activo',
        'ultimo_acceso',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'ultimo_acceso' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Verifica si el administrador está activo
     */
    public function isActivo(): bool
    {
        return $this->activo;
    }

    /**
     * Verifica si es super administrador
     */
    public function isSuperAdmin(): bool
    {
        return $this->tipo === 'super_admin';
    }

    /**
     * Verifica si tiene permisos de administrador completo
     */
    public function tienePermisoCompleto(): bool
    {
        return in_array($this->tipo, ['super_admin', 'admin']);
    }

    /**
     * Obtiene el nombre del tipo de administrador
     */
    public function getTipoNombreAttribute(): string
    {
        return match($this->tipo) {
            'super_admin' => 'Super Administrador',
            'admin' => 'Administrador',
            'editor' => 'Editor',
            default => $this->tipo,
        };
    }

    /**
     * Actualiza el último acceso
     */
    public function actualizarUltimoAcceso(): void
    {
        $this->ultimo_acceso = now();
        $this->save();
    }
}
