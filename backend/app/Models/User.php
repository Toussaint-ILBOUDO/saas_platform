<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens;
    use HasPushSubscriptions;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;
    use InteractsWithMedia;

    protected $fillable = [
        'nom',
        'prenom',
        'telephone_whatsapp',
        'telephone_appel',
        'email',
        'password',
        'statut',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Mail de réinitialisation personnalisé (T3.2) au lieu du mail du framework.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ReinitialisationMotDePasse($token));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'statut' => 'boolean',
        ];
    }

     /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function parentProfil()
    {
        return $this->hasOne(ParentProfil::class);
    }

    public function enseignantProfil()
    {
        return $this->hasOne(EnseignantProfil::class);
    }

    public function eleve()
    {
        return $this->hasOne(Eleve::class);
    }

    public function enfants()
    {
        return $this->hasMany(Eleve::class, 'parent_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function documents()
    {
        return $this->hasMany(DocumentBibliotheque::class, 'user_id');
    }

    public function favoris()
    {
        return $this->hasMany(FavoriBibliotheque::class, 'user_id');
    }

    public function notes()
    {
        return $this->hasMany(DocumentNote::class, 'user_id');
    }

    public function commentaires()
    {
        return $this->hasMany(DocumentCommentaire::class, 'user_id');
    }

    public function signalements()
    {
        return $this->hasMany(DocumentSignalement::class, 'user_id');
    }

    public function getPhotoProfilUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('photo_profil')
            ?: asset('templates/publicpages/assets/img/default-teacher.png');
    }

}