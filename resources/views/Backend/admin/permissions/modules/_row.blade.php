<tr style="border-bottom: 1px solid rgba(255,255,255,0.05);{{ $depth > 0 ? ' display: none;' : '' }}" class="{{ $module->children->isNotEmpty() ? 'collapsed' : '' }}" data-id="{{ $module->id }}" data-ancestors="{{ implode(',', $ancestors ?? []) }}">
    <td style="padding: 0.75rem; font-weight: {{ $depth == 0 ? '600' : '500' }};">
        {!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth) !!} 
        @if($depth > 0)
            <span style="color: var(--text-muted); margin-right: 0.25rem;">↳</span>
        @endif
        @if($module->children->isNotEmpty())
            <span class="toggle-category" style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; margin-right: 0.25rem; color: var(--text-muted);" title="Zwiń/Rozwiń">
                <svg class="toggle-icon" style="width: 12px; height: 12px; transition: transform 0.2s;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                </svg>
            </span>
        @else
            <span style="display: inline-flex; width: 20px; height: 20px; margin-right: 0.25rem;"></span>
        @endif
        {{ $module->nazwa }}
    </td>
    <td style="padding: 0.75rem;">
        @if($module->tags && $module->tags->isNotEmpty())
            <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                @foreach($module->tags as $tag)
                    <a href="{{ route('modules.index', ['tag_id' => $tag->id]) }}"
                       style="background: rgba(99, 102, 241, 0.12); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.25); padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600; text-decoration: none; white-space: nowrap;">
                        #{{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @else
            <span style="color: var(--text-muted); font-size: 0.85rem;">—</span>
        @endif
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
    @include('Backend.admin.permissions.modules._row', ['module' => $child, 'depth' => $depth + 1, 'ancestors' => array_merge($ancestors ?? [], [$module->id])])
@endforeach
