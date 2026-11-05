@props(['role'])

<span @class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
    'bg-brand-900 text-white' => $role === \App\Enums\Role::Admin,
    'bg-slate-100 text-slate-700' => $role === \App\Enums\Role::Editor,
])>
    <span @class([
        'size-1.5 rounded-full',
        'bg-white' => $role === \App\Enums\Role::Admin,
        'bg-slate-400' => $role === \App\Enums\Role::Editor,
    ])></span>
    {{ $role->label() }}
</span>
