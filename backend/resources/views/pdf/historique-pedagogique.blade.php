<h1>
Historique pédagogique
</h1>

<p>
Élève :
{{ $eleve->user->name }}
</p>

<table>

<thead>

<tr>
    <th>Date</th>
    <th>Matière</th>
    <th>Enseignant</th>
    <th>Durée</th>
</tr>

</thead>

<tbody>

@foreach($cahiers as $cahier)

<tr>

    <td>
        {{ $cahier->date_seance->format('d/m/Y') }}
    </td>

    <td>
        {{ $cahier->affectation->matiere->nom }}
    </td>

    <td>
        {{ $cahier->affectation->enseignant->user->name }}
    </td>

    <td>
        {{ $cahier->duree_heures }} h
    </td>

</tr>

@endforeach

</tbody>

</table>