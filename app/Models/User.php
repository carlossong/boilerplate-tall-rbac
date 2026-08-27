<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Auth\Models\User as DomainUser;

/**
 * Model de compatibilidade que estende o User do domínio de autenticação.
 */
class User extends DomainUser {}
