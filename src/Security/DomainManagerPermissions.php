<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Security;

final class DomainManagerPermissions
{
    public const FIELD = 'domain_manager_permissions';

    public const EDIT_RECORDS = 'edit_records';
    public const TEST_CONNECTIONS = 'test_connections';
    public const MANAGE_SECRETS = 'manage_secrets';
    public const EDIT_SETTINGS = 'edit_settings';

    public static function attribute(): string
    {
        return 'contao_user.'.self::FIELD;
    }
}
