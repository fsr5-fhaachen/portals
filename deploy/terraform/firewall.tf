resource "hcloud_firewall" "this" {
  name = var.domain_name
  rule {
    description = "Allow ICMP"
    direction   = "in"
    protocol    = "icmp"
    source_ips = [
      "0.0.0.0/0",
      "::/0"
    ]
  }
  rule {
    description = "Allow SSH"
    direction   = "in"
    protocol    = "tcp"
    port        = "22"
    source_ips = [
      "0.0.0.0/0",
      "::/0"
    ]
  }
  rule {
    description = "Allow HTTP"
    direction   = "in"
    protocol    = "tcp"
    port        = "80"
    source_ips = [
      "0.0.0.0/0",
      "::/0"
    ]
  }
  rule {
    description = "Allow HTTPS"
    direction   = "in"
    protocol    = "tcp"
    port        = "443"
    source_ips = [
      "0.0.0.0/0",
      "::/0"
    ]
  }
  dynamic "rule" {
    for_each = var.monitoring_enabled ? local.monitoring_exporters : {}
    content {
      description = "Allow ${rule.key} from monitoring server"
      direction   = "in"
      protocol    = "tcp"
      port        = rule.value
      source_ips = [
        "${hcloud_primary_ip.monitoring_ipv4[0].ip_address}/32",
        "${hcloud_primary_ip.monitoring_ipv6[0].ip_address}1/128"
      ]
    }
  }
}

resource "hcloud_firewall_attachment" "this" {
  firewall_id = hcloud_firewall.this.id
  server_ids = [
    hcloud_server.this.id
  ]
}

resource "hcloud_firewall" "monitoring" {
  count = var.monitoring_enabled ? 1 : 0
  name  = var.monitoring_domain_name
  rule {
    description = "Allow ICMP"
    direction   = "in"
    protocol    = "icmp"
    source_ips = [
      "0.0.0.0/0",
      "::/0"
    ]
  }
  rule {
    description = "Allow SSH"
    direction   = "in"
    protocol    = "tcp"
    port        = "22"
    source_ips = [
      "0.0.0.0/0",
      "::/0"
    ]
  }
  rule {
    description = "Allow HTTP"
    direction   = "in"
    protocol    = "tcp"
    port        = "80"
    source_ips = [
      "0.0.0.0/0",
      "::/0"
    ]
  }
  rule {
    description = "Allow HTTPS"
    direction   = "in"
    protocol    = "tcp"
    port        = "443"
    source_ips = [
      "0.0.0.0/0",
      "::/0"
    ]
  }
}

resource "hcloud_firewall_attachment" "monitoring" {
  count       = var.monitoring_enabled ? 1 : 0
  firewall_id = hcloud_firewall.monitoring[0].id
  server_ids = [
    hcloud_server.monitoring[0].id
  ]
}
