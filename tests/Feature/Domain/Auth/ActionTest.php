<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Actions\CreatePermissionAction;
use App\Domain\Auth\Actions\CreateRoleAction;
use App\Domain\Auth\Actions\CreateUserAction;
use App\Domain\Auth\Actions\DeletePermissionAction;
use App\Domain\Auth\Actions\DeleteRoleAction;
use App\Domain\Auth\Actions\DeleteUserAction;
use App\Domain\Auth\Actions\RestorePermissionAction;
use App\Domain\Auth\Actions\RestoreRoleAction;
use App\Domain\Auth\Actions\RestoreUserAction;
use App\Domain\Auth\Actions\UpdatePermissionAction;
use App\Domain\Auth\Actions\UpdateUserAction;
use App\Domain\Auth\DTOs\PermissionData;
use App\Domain\Auth\DTOs\RoleData;
use App\Domain\Auth\DTOs\UserData;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_update_user_action(): void
    {
        $role = Role::factory()->create();

        $createAction = app(CreateUserAction::class);
        $userData = new UserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secret-password',
            isSuperAdmin: false,
            roleIds: [$role->id],
        );

        $user = $createAction($userData);

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertTrue($user->roles->contains($role));

        $updateAction = app(UpdateUserAction::class);
        $updateData = new UserData(
            name: 'John Updated',
            email: 'john.updated@example.com',
            password: null, // não altera senha
            isSuperAdmin: true,
            roleIds: [],
        );

        $updatedUser = $updateAction($user, $updateData);

        $this->assertEquals('John Updated', $updatedUser->name);
        $this->assertEquals('john.updated@example.com', $updatedUser->email);
        $this->assertTrue($updatedUser->is_super_admin);
        $this->assertTrue(Hash::check('secret-password', $updatedUser->password));
        $this->assertCount(0, $updatedUser->fresh()->roles);
    }

    public function test_delete_and_restore_user_action(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $deleteAction = app(DeleteUserAction::class);

        // Bloqueia auto-exclusão
        $this->expectException(DomainException::class);
        $deleteAction($user, $user);

        // Permite excluir outro usuário
        $deleteAction($otherUser, $user);
        $this->assertTrue($otherUser->fresh()->trashed());

        $restoreAction = app(RestoreUserAction::class);
        $restoreAction($otherUser);
        $this->assertFalse($otherUser->fresh()->trashed());
    }

    public function test_create_update_and_delete_role_action(): void
    {
        $permission = Permission::factory()->create();
        $createAction = app(CreateRoleAction::class);

        $role = $createAction(new RoleData(
            name: 'Editor',
            slug: 'editor',
            description: 'Can edit content',
            permissionIds: [$permission->id],
        ));

        $this->assertEquals('Editor', $role->name);
        $this->assertTrue($role->permissions->contains($permission));

        $deleteAction = app(DeleteRoleAction::class);
        $deleteAction($role);
        $this->assertTrue($role->fresh()->trashed());

        $restoreAction = app(RestoreRoleAction::class);
        $restoreAction($role);
        $this->assertFalse($role->fresh()->trashed());

        // Bloqueia exclusão de admin
        $adminRole = Role::factory()->create(['slug' => 'admin']);
        $this->expectException(DomainException::class);
        $deleteAction($adminRole);
    }

    public function test_permission_actions(): void
    {
        $createAction = app(CreatePermissionAction::class);
        $permission = $createAction(new PermissionData(
            name: 'Publish Articles',
            slug: 'articles.publish',
            description: 'Allows publishing articles',
        ));

        $this->assertEquals('articles.publish', $permission->slug);

        $updateAction = app(UpdatePermissionAction::class);
        $updated = $updateAction($permission, new PermissionData(
            name: 'Publish and Unpublish Articles',
            slug: 'articles.publish',
            description: 'Updated description',
        ));
        $this->assertEquals('Publish and Unpublish Articles', $updated->name);

        $deleteAction = app(DeletePermissionAction::class);
        $deleteAction($permission);
        $this->assertTrue($permission->fresh()->trashed());

        $restoreAction = app(RestorePermissionAction::class);
        $restoreAction($permission);
        $this->assertFalse($permission->fresh()->trashed());
    }
}
