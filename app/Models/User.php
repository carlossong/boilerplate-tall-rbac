<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Auth\Models\User as DomainUser;

/**
 * Compatibility model extending the authentication domain User.
 */
class User extends DomainUser {}
