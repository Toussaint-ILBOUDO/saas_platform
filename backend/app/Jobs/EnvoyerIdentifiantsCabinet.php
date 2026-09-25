<?php

namespace App\Jobs;

use App\Mail\CabinetIdentifiants;
use App\Models\Cabinet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Envoi de l'e-mail d'identifiants d'un nouveau cabinet (T3.6).
 * Découplé du pipeline : dispatché par « CreerAdminCabinetEtNotifier » (T2.4)
 * et exécuté en file d'attente (database centrale) — le pipeline ne bloque pas.
 */
class EnvoyerIdentifiantsCabinet implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Cabinet $cabinet,
        public string $email,
        public string $password,
    ) {
    }

    public function handle(): void
    {
        if ($this->cabinet->email) {
            Mail::to($this->cabinet->email)->send(
                new CabinetIdentifiants($this->cabinet, $this->email, $this->password)
            );
        }
    }
}