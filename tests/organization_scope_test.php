<?php

require __DIR__.'/../src/Support/OrganizationScope.php';

use Kazispace\Bridge\Support\OrganizationScope;

function expect_org(bool $condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, $message.PHP_EOL);
        exit(1);
    }
}

$company = new stdClass();
$company->company_uuid = 'company-a';
$company->company_id = 99;
expect_org(OrganizationScope::fromUser($company) === 'company-a', 'company_uuid wins over company_id');

$legacy = new stdClass();
$legacy->company_id = 99;
expect_org(OrganizationScope::fromUser($legacy) === null, 'company_id alone does not resolve an organization');

$empty = new stdClass();
$empty->company_uuid = '';
$empty->company_id = 'company-a';
expect_org(OrganizationScope::fromUser($empty) === null, 'empty company_uuid is not an organization');

expect_org(OrganizationScope::fromUser(null) === null, 'missing user is not an organization');

echo "ok\n";
