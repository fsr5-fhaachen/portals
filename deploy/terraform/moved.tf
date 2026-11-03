# Monitoring resources became optional (var.monitoring_enabled) and are now counted.
# These blocks keep existing states from destroying and recreating them.

moved {
  from = hcloud_primary_ip.monitoring_ipv4
  to   = hcloud_primary_ip.monitoring_ipv4[0]
}

moved {
  from = hcloud_primary_ip.monitoring_ipv6
  to   = hcloud_primary_ip.monitoring_ipv6[0]
}

moved {
  from = hcloud_rdns.monitoring_ipv4
  to   = hcloud_rdns.monitoring_ipv4[0]
}

moved {
  from = hcloud_rdns.monitoring_ipv6
  to   = hcloud_rdns.monitoring_ipv6[0]
}

moved {
  from = hcloud_server.monitoring
  to   = hcloud_server.monitoring[0]
}

moved {
  from = hcloud_firewall.monitoring
  to   = hcloud_firewall.monitoring[0]
}

moved {
  from = hcloud_firewall_attachment.monitoring
  to   = hcloud_firewall_attachment.monitoring[0]
}

moved {
  from = cloudflare_dns_record.monitoring_ipv4
  to   = cloudflare_dns_record.monitoring_ipv4[0]
}

moved {
  from = cloudflare_dns_record.monitoring_ipv6
  to   = cloudflare_dns_record.monitoring_ipv6[0]
}
