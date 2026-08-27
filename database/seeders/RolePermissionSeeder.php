<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Register Canonical Permissions
        $permissions = [
            // Users
            ['name' => 'Visualizar Usuários', 'slug' => 'users.view', 'description' => 'Permite visualizar a lista de usuários e os detalhes dos perfis.'],
            ['name' => 'Criar Usuários', 'slug' => 'users.create', 'description' => 'Permite cadastrar novas contas de usuário.'],
            ['name' => 'Atualizar Usuários', 'slug' => 'users.update', 'description' => 'Permite atualizar perfis de usuário e atribuições de função.'],
            ['name' => 'Excluir Usuários', 'slug' => 'users.delete', 'description' => 'Permite excluir e restaurar contas de usuário.'],

            // Departments
            ['name' => 'Visualizar Departamentos', 'slug' => 'departments.view', 'description' => 'Permite visualizar departamentos e unidades da empresa.'],
            ['name' => 'Criar Departamentos', 'slug' => 'departments.create', 'description' => 'Permite criar novos departamentos.'],
            ['name' => 'Atualizar Departamentos', 'slug' => 'departments.update', 'description' => 'Permite atualizar dados do departamento e vínculos de função.'],
            ['name' => 'Excluir Departamentos', 'slug' => 'departments.delete', 'description' => 'Permite excluir e restaurar departamentos.'],

            // Roles
            ['name' => 'Visualizar Funções', 'slug' => 'roles.view', 'description' => 'Permite visualizar as funções do sistema e as permissões atribuídas.'],
            ['name' => 'Criar Funções', 'slug' => 'roles.create', 'description' => 'Permite definir novas funções do sistema.'],
            ['name' => 'Atualizar Funções', 'slug' => 'roles.update', 'description' => 'Permite atualizar funções e atribuições de permissão.'],
            ['name' => 'Excluir Funções', 'slug' => 'roles.delete', 'description' => 'Permite excluir logicamente e restaurar funções.'],

            // Permissions
            ['name' => 'Visualizar Permissões', 'slug' => 'permissions.view', 'description' => 'Permite visualizar habilidades de permissão e slugs do sistema.'],
            ['name' => 'Criar Permissões', 'slug' => 'permissions.create', 'description' => 'Permite cadastrar novos slugs de permissão personalizados.'],
            ['name' => 'Atualizar Permissões', 'slug' => 'permissions.update', 'description' => 'Permite atualizar nomes e descrições de permissão.'],
            ['name' => 'Excluir Permissões', 'slug' => 'permissions.delete', 'description' => 'Permite excluir habilidades de permissão.'],

            // Audit
            ['name' => 'Visualizar Logs de Auditoria', 'slug' => 'audit-logs.view', 'description' => 'Permite visualizar quem atribuiu ou revogou funções e permissões.'],
        ];

        $createdPermissions = [];
        foreach ($permissions as $permData) {
            $createdPermissions[$permData['slug']] = Permission::updateOrCreate(
                ['slug' => $permData['slug']],
                [
                    'name' => $permData['name'],
                    'description' => $permData['description'],
                    'group' => Permission::groupFromSlug($permData['slug']),
                ],
            );
        }

        // 2. Register Departments
        $executive = Department::updateOrCreate(
            ['slug' => 'executive-board'],
            [
                'name' => 'Diretoria Executiva',
                'description' => 'Liderança executiva, governança corporativa e estratégia global.',
                'is_active' => true,
            ],
        );

        $finance = Department::updateOrCreate(
            ['slug' => 'finance'],
            [
                'name' => 'Departamento Financeiro',
                'description' => 'Contas a pagar, contas a receber, aprovações de orçamento e tesouraria.',
                'is_active' => true,
            ],
        );

        $operations = Department::updateOrCreate(
            ['slug' => 'operations'],
            [
                'name' => 'Operações e Logística',
                'description' => 'Gestão de frota, roteirização, turnos e logística operacional.',
                'is_active' => true,
            ],
        );

        $hr = Department::updateOrCreate(
            ['slug' => 'human-resources'],
            [
                'name' => 'Recursos Humanos',
                'description' => 'Gestão de talentos, organização departamental e integração de colaboradores.',
                'is_active' => true,
            ],
        );

        // 3. Register Roles with Hierarchical Levels
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrador',
                'level' => 80,
                'is_system' => true,
                'description' => 'Acesso administrativo completo a todos os recursos da empresa.',
            ],
        );
        $adminRole->permissions()->sync(collect($createdPermissions)->pluck('id'));

        $managerRole = Role::updateOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Gerente de Departamento',
                'level' => 50,
                'description' => 'Gestão de equipe e autorização operacional dentro do departamento.',
            ],
        );
        $managerPermissionIds = collect($createdPermissions)
            ->filter(fn ($p, $slug) => str_starts_with($slug, 'users.') || str_starts_with($slug, 'departments.view'))
            ->pluck('id');
        $managerRole->permissions()->sync($managerPermissionIds);

        $operatorRole = Role::updateOrCreate(
            ['slug' => 'operator'],
            [
                'name' => 'Operador',
                'level' => 20,
                'description' => 'Acesso operacional básico para executar rotinas e tarefas do setor.',
            ],
        );

        $viewerRole = Role::updateOrCreate(
            ['slug' => 'viewer'],
            [
                'name' => 'Visualizador',
                'level' => 10,
                'description' => 'Acesso somente leitura para visualizar usuários, funções e permissões.',
            ],
        );
        $viewPermissionIds = collect($createdPermissions)
            ->filter(fn ($p, $slug) => str_ends_with($slug, '.view'))
            ->pluck('id');
        $viewerRole->permissions()->sync($viewPermissionIds);

        // Link roles to model departments
        $finance->roles()->syncWithoutDetaching([$adminRole->id, $managerRole->id, $viewerRole->id]);
        $operations->roles()->syncWithoutDetaching([$managerRole->id, $operatorRole->id, $viewerRole->id]);
        $executive->roles()->syncWithoutDetaching([$adminRole->id, $managerRole->id]);

        // 4. Register Demo Users
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Superadministrador',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
                'email_verified_at' => now(),
            ],
        );
        $superAdmin->roles()->sync([$adminRole->id]);
        $superAdmin->departments()->sync([
            $executive->id => ['id' => (string) Str::uuid(), 'role_id' => $adminRole->id, 'is_primary' => true],
            $finance->id => ['id' => (string) Str::uuid(), 'role_id' => $adminRole->id, 'is_primary' => false],
        ]);

        $managerUser = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Gerente de Operações',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
                'email_verified_at' => now(),
            ],
        );
        $managerUser->roles()->sync([$managerRole->id]);
        $managerUser->departments()->sync([
            $operations->id => ['id' => (string) Str::uuid(), 'role_id' => $managerRole->id, 'is_primary' => true],
        ]);

        $viewerUser = User::updateOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Usuário Visualizador',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
                'email_verified_at' => now(),
            ],
        );
        $viewerUser->roles()->sync([$viewerRole->id]);
        $viewerUser->departments()->sync([
            $finance->id => ['id' => (string) Str::uuid(), 'role_id' => $viewerRole->id, 'is_primary' => true],
        ]);
    }
}
