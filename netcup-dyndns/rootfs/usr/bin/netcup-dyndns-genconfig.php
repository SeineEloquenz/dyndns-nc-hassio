#!/usr/bin/env php
<?php

/**
 * Translates the Home Assistant app options into a config.php for update.php.
 */

const OPTIONS_PATH = '/data/options.json';
const CONFIG_PATH = '/tmp/config.php';
const CACHE_PATH = '/data/cache.json';
const DEFAULT_API_URL = 'https://ccp.netcup.net/run/webservice/servers/endpoint.php?JSON';

const TEXT_OPTIONS = [
    'customer_number' => 'CUSTOMERNR',
    'api_key' => 'APIKEY',
    'api_password' => 'APIPASSWORD',
    'domains' => 'DOMAINLIST',
    'clouddns_api_key' => 'CLOUDDNS_DYNDNS_APIKEY',
    'clouddns_domains' => 'DOMAINLIST_CLOUDDNS_DYNDNS',
    'clouddns_api_url' => 'CLOUDDNS_DYNDNS_APIURL',
    'ipv4_url' => 'IPV4_ADDRESS_URL',
    'ipv4_url_fallback' => 'IPV4_ADDRESS_URL_FALLBACK',
    'ipv6_url' => 'IPV6_ADDRESS_URL',
    'ipv6_url_fallback' => 'IPV6_ADDRESS_URL_FALLBACK',
];

const NUMBER_OPTIONS = [
    'jitter_max' => 'JITTER_MAX',
    'retry_sleep' => 'RETRY_SLEEP',
];

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function define_line(string $constant, string $value): string
{
    return sprintf('define(%s, %s);', var_export($constant, true), $value);
}

$raw = @file_get_contents(OPTIONS_PATH);
if ($raw === false) {
    fail('Could not read ' . OPTIONS_PATH . '.');
}

try {
    $options = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    fail('Could not parse ' . OPTIONS_PATH . ': ' . $exception->getMessage());
}

if (!is_array($options)) {
    fail(OPTIONS_PATH . ' does not contain a configuration object.');
}

$text = static function (string $key) use ($options): ?string {
    $value = $options[$key] ?? null;
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);

    return $value === '' ? null : $value;
};

$domains = $text('domains');
$clouddnsDomains = $text('clouddns_domains');

if ($domains === null && $clouddnsDomains === null) {
    fail(
        'Nothing to update. Set "Domains" for domains with a DNS tab in the netcup CCP, '
        . '"CloudDNS domains" for domains with a CloudDNS tab, or both.'
    );
}

if ($domains !== null) {
    $required = [
        'customer_number' => 'Customer number',
        'api_key' => 'Legacy API key',
        'api_password' => 'API password',
    ];

    $missing = [];
    foreach ($required as $key => $label) {
        if ($text($key) === null) {
            $missing[] = $label;
        }
    }

    if ($missing !== []) {
        fail(
            'Missing configuration for the classic CCP DNS API, required because "Domains" is set: '
            . implode(', ', $missing) . '.'
        );
    }
}

if ($clouddnsDomains !== null && $text('clouddns_api_key') === null) {
    fail(
        'Missing "CloudDNS API key", required because "CloudDNS domains" is set. Create one in '
        . 'the netcup CCP under master data > API in the "API-Keys" section. A Legacy API key '
        . 'does not work here.'
    );
}

$lines = ['<?php', ''];

foreach (TEXT_OPTIONS as $key => $constant) {
    $value = $text($key);
    if ($value !== null) {
        $lines[] = define_line($constant, var_export($value, true));
    }
}

// update.php dereferences APIURL without a defined() guard.
$lines[] = define_line('APIURL', var_export($text('api_url') ?? DEFAULT_API_URL, true));

$flags = [
    'USE_IPV4' => (bool) ($options['use_ipv4'] ?? true),
    'USE_IPV6' => (bool) ($options['use_ipv6'] ?? false),
    'CHANGE_TTL' => (bool) ($options['change_ttl'] ?? true),
];

foreach ($flags as $constant => $value) {
    $lines[] = define_line($constant, var_export($value, true));
}

foreach (NUMBER_OPTIONS as $key => $constant) {
    $value = $options[$key] ?? null;
    if (is_int($value)) {
        $lines[] = define_line($constant, (string) $value);
    }
}

$lines[] = define_line('CACHE_FILE', var_export(CACHE_PATH, true));

// Set before the write so the credentials are never briefly world-readable.
umask(0077);

if (file_put_contents(CONFIG_PATH, implode(PHP_EOL, $lines) . PHP_EOL) === false) {
    fail('Could not write ' . CONFIG_PATH . '.');
}

chmod(CONFIG_PATH, 0600);
