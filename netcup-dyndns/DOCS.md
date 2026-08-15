# netcup Dynamic DNS

Keeps your netcup DNS records pointed at the current public IP address of your Home Assistant
instance, using [stecklars/dynamic-dns-netcup-api](https://github.com/stecklars/dynamic-dns-netcup-api).

## Which API do you need?

netcup runs two DNS systems, and your domains use one or the other. Open a domain in the
[customer control panel](https://www.customercontrolpanel.de) and look at the tabs:

| Tab shown | System | Options to fill in |
| --- | --- | --- |
| **DNS** | Classic CCP DNS API | Customer number, Legacy API key, API password, Domains |
| **CloudDNS** | CloudDNS DynDNS API | CloudDNS API key, CloudDNS domains |

netcup is migrating domains to CloudDNS over time. A domain belongs in exactly one of the two
domain lists, never both. If you have some of each, fill in both sets and the app updates them in
a single run.

## Getting your credentials

Both key types live in the CCP under **master data → API**, in two separate sections:

- **Legacy-API-Keys** gives you the *Legacy API key* and *API password* used by the classic DNS
  API. Both are needed, along with your customer number.
- **API-Keys** gives you the regular *API key* used by CloudDNS. This is a different key. A
  Legacy key will not work for CloudDNS, and vice versa.

## Domain list format

Both **Domains** and **CloudDNS domains** use the same syntax:

```
example.com: @, www, server; other.tld: home
```

Start with the domain, add `:`, then a comma-separated list of subdomains. Separate domains with
`;`. Whitespace and line breaks are ignored, so long lists can be spread over several lines.

- `@` is the domain root, so `example.com` itself.
- `*` is a wildcard covering every subdomain not otherwise defined in DNS.

Subdomains that do not exist yet are created for you.

## Configuration

### Required

At least one domain list, plus the credentials that list needs.

| Option | Notes |
| --- | --- |
| `domains` | Classic CCP DNS API. Requires `customer_number`, `api_key`, `api_password`. |
| `clouddns_domains` | CloudDNS DynDNS API. Requires `clouddns_api_key`. |

The app refuses to start with a message naming exactly what is missing.

### Common

| Option | Default | Notes |
| --- | --- | --- |
| `use_ipv4` | `true` | Keeps A records in sync. Turn off behind carrier grade NAT. |
| `use_ipv6` | `false` | Keeps AAAA records in sync. Only turn this on if you actually have IPv6, otherwise every run fails. |
| `change_ttl` | `true` | Lowers the TTL of updated records to 300 seconds. Ignored for CloudDNS domains, which always get 300 seconds. |
| `update_interval` | `5` | Minutes between checks. Runs where your IP has not changed skip the netcup API entirely, so short intervals cost little. |

### Advanced

Leave these empty unless you have a reason not to.

| Option | Default |
| --- | --- |
| `jitter_max` | `30` seconds of random delay before API calls, spreading load across users |
| `retry_sleep` | `30` seconds before retrying after a network error |
| `ipv4_url` | `https://get-ipv4.steck.cc` |
| `ipv4_url_fallback` | `https://ipv4.seeip.org` |
| `ipv6_url` | `https://get-ipv6.steck.cc` |
| `ipv6_url_fallback` | `https://v6.ident.me` |
| `api_url` | `https://ccp.netcup.net/run/webservice/servers/endpoint.php?JSON` |
| `clouddns_api_url` | `https://customercontrolpanel.de/wsDynDns.php` |

## Example

Classic DNS for one domain, CloudDNS for another:

```yaml
customer_number: "123456"
api_key: "your-legacy-api-key"
api_password: "your-api-password"
domains: "example.com: @, www"
clouddns_api_key: "your-regular-api-key"
clouddns_domains: "other.tld: @, home"
use_ipv4: true
use_ipv6: false
change_ttl: true
update_interval: 5
```

## How it works

On start the app turns your options into a config file and then runs the updater every
`update_interval` minutes. After a successful update the current IP is cached in `/data`, so
later runs that find the same address skip the netcup API. The cache survives restarts and
updates of the app.

## Troubleshooting

**"Nothing to update"**. Neither domain list is filled in. Add at least one.

**The log reports statuscode 5029, or says the zone contains no DNS records**. The domain has
been migrated to CloudDNS. Move it from `domains` to `clouddns_domains` and set
`clouddns_api_key`.

**Authentication fails for CloudDNS**. You are probably using a Legacy API key. CloudDNS needs a
key from the "API-Keys" section of the CCP.

**Every run fails while looking up the IPv6 address**. Turn off `use_ipv6` if your connection has
no IPv6 connectivity.

**Records do not change even though your IP did**. Restart the app. That clears nothing by
itself, but the log will show whether the updater considers the cached address current.

## Credits

The updater is [stecklars/dynamic-dns-netcup-api](https://github.com/stecklars/dynamic-dns-netcup-api),
bundled unmodified. This app packages it for Home Assistant.
