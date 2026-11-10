<?php

namespace App\Mcp\Tools;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a panel user (admin or editor). If no password is given a strong one is generated and returned — tell it to the user once.')]
class CreateUser extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'first_name' => $schema->string()->required(),
            'last_name' => $schema->string()->required(),
            'email' => $schema->string()->required(),
            'role' => $schema->string()->enum(['admin', 'editor'])->description('editor = content only, admin = everything incl. users (default editor)'),
            'locale' => $schema->string()->enum(['tr', 'en'])->description('Panel language (default tr)'),
            'password' => $schema->string()->description('At least 8 characters; empty = generate'),
        ];
    }

    protected function run(Request $request): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', fn ($attribute, $value, $fail) => \App\Models\User::findByEmail($value) ? $fail('A user with this email already exists.') : null],
            'role' => ['nullable', Rule::enum(Role::class)],
            'locale' => ['nullable', Rule::in(['tr', 'en'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        $password = $data['password'] ?? Str::password(14, symbols: false);

        $user = User::create([
            'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $data['email'],
            'role' => $data['role'] ?? Role::Editor->value, 'locale' => $data['locale'] ?? 'tr', 'password' => $password,
        ]);

        return ['result' => 'created', 'id' => $user->id, 'email' => $user->email, 'role' => $user->role->value, 'password' => $password, 'login_url' => route('login')];
    }
}
