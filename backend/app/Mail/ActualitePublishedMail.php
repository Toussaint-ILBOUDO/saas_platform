<?php

namespace App\Mail;

use App\Models\Actualite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ActualitePublishedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Actualite $actualite
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Nouvelle actualité : '.$this->actualite->titre)
            ->view('emails.actualites.publiee')
            ->with([
                'url' => route('actualites.show', $this->actualite->slug),
            ]);
    }
}
