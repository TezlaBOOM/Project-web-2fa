<option value="{{ $module->id }}" {{ $selectedId == $module->id ? 'selected' : '' }} {{ isset($disabledId) && $disabledId == $module->id ? 'disabled' : '' }}>
    {!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth) !!} {{ $depth > 0 ? '↳ ' : '' }}{{ $module->nazwa }}
</option>
@foreach($module->children as $child)
    @include('Backend.admin.permissions.modules._option', ['module' => $child, 'depth' => $depth + 1, 'selectedId' => $selectedId, 'disabledId' => $disabledId ?? null])
@endforeach
