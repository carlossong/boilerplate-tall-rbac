<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auth\Enums\PermissionAuditAction;
use App\Domain\Auth\Models\PermissionAuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermissionAuditLog>
 */
class PermissionAuditLogFactory extends Factory
{
    protected $model = PermissionAuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action' => PermissionAuditAction::Assigned,
            'actor_id' => null,
            'actor_name' => fake()->name(),
            'actor_email' => fake()->safeEmail(),
            'subject_type' => PermissionAuditLog::SUBJECT_USER,
            'subject_id' => fake()->uuid(),
            'subject_name' => fake()->name(),
            'grantable_type' => PermissionAuditLog::GRANTABLE_ROLE,
            'grantable_id' => fake()->uuid(),
            'grantable_name' => fake()->jobTitle(),
            'department_id' => null,
            'department_name' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'created_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'action' => PermissionAuditAction::Revoked,
        ]);
    }

    public function permissionGrant(): static
    {
        return $this->state(fn () => [
            'subject_type' => PermissionAuditLog::SUBJECT_ROLE,
            'grantable_type' => PermissionAuditLog::GRANTABLE_PERMISSION,
            'grantable_name' => 'users.view',
        ]);
    }
}
