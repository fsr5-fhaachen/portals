resource "cloudflare_dns_record" "ipv4" {
  zone_id = var.cloudflare_zone_id
  name    = var.domain_name
  ttl     = 3600
  type    = "A"
  comment = "Managed by OpenTofu"
  content = hcloud_primary_ip.ipv4.ip_address
  proxied = false
}

resource "cloudflare_dns_record" "ipv6" {
  zone_id = var.cloudflare_zone_id
  name    = var.domain_name
  ttl     = 3600
  type    = "AAAA"
  comment = "Managed by OpenTofu"
  content = "${hcloud_primary_ip.ipv6.ip_address}1"
  proxied = false
}
