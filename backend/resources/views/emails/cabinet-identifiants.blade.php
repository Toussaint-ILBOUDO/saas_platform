@component('mail::message')
# Bienvenue sur SaasCD

**Félicitations, {{ $cabinet->nom }} est en ligne sur la plateforme SaasCD !**

Votre espace cabinet est prêt : élèves, enseignants et outils de gestion sont accessibles depuis votre domaine.

@component('mail::button', ['url' => 'http://' . $cabinet->primary_domain])
Accéder au cabinet
@endcomponent

Vos identifiants d'administration :

- **Adresse** : {{ $email }}
- **Mot de passe** : {{ $password }}

Merci,<br>
L'équipe {{ config('app.name') }}
@endcomponent