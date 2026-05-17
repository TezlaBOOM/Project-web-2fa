<tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
    <td style="padding: 0.75rem; font-weight: {{ $depth == 0 ? '600' : '500' }};">
        {!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth) !!} 
        @if($depth > 0)
            <span style="color: var(--text-muted); margin-right: 0.5rem;">↳</span>
        @endif
        {{ $module->nazwa }}
    </td>
    <td style="padding: 0.75rem; text-align: right; white-space: nowrap;">
        @if($depth < 4)
            <a href="{{ route('modules.create', ['parent_id' => $module->id]) }}" style="color: var(--success); text-decoration: none; margin-right: 1rem; font-size: 0.85rem;" title="Dodaj podkategorię pod tym modułem">+ Podkategoria</a>
        @endif
        <a href="{{ route('modules.edit', $module->id) }}" style="color: var(--primary); text-decoration: none; margin-right: 1rem; font-size: 0.85rem;">Edytuj</a>
        <form action="{{ route('modules.destroy', $module->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Czy na pewno chcesz usunąć ten moduł? Usunięcie modułu usunie również wszystkie jego podkategorie!');">
            @csrf
            @method('DELETE')
            <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 0.85rem; padding: 0;">Usuń</button>
        </form>
    </td>
</tr>

@foreach($module->children as $child)
    @include('Backend.admin.permissions.modules._row', ['module' => $child, 'depth' => $depth + 1])
@endforeach
