@if (session('impersonation'))
    @php $imp = session('impersonation'); @endphp
    <div class="impersonation-banniere" style="background:#6610f2;color:#fff;padding:.5rem 1rem;display:flex;gap:1rem;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:1080">
        <span>
            <i class="bi bi-eye" style="margin-right:.5rem"></i>
            <strong>Mode impersonation</strong> — vous agissez en tant qu'administrateur du cabinet
            <strong>{{ tenant('nom') }}</strong> (super-admin n°{{ $imp['super_admin_id'] }}).
        </span>
        <form method="POST" action="{{ route('impersonation.sortir') }}">
            @csrf
            <button type="submit" class="btn btn-light btn-sm" style="color:#6610f2;font-weight:600">Quitter l'impersonation</button>
        </form>
    </div>
@endif