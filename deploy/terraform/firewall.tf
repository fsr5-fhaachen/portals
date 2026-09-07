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
  rule {
    description = "Allow Node Exporter from monitoring server"
    direction   = "in"
    protocol    = "tcp"
    port        = "9100"
    source_ips = [
      "${hcloud_primary_ip.monitoring_ipv4.ip_address}/32",
      "${hcloud_primary_ip.monitoring_ipv6.ip_address}1/128"
    ]
  }
  rule {
    description = "Allow Postgres Exporter from monitoring server"
    direction   = "in"
    protocol    = "tcp"
    port        = "9187"
    source_ips = [
      "${hcloud_primary_ip.monitoring_ipv4.ip_address}/32",
      "${hcloud_primary_ip.monitoring_ipv6.ip_address}1/128"
    ]
  }
  rule {
    description = "Allow Redis Exporter from monitoring server"
    direction   = "in"
    protocol    = "tcp"
    port        = "9121"
    source_ips = [
      "${hcloud_primary_ip.monitoring_ipv4.ip_address}/32",
      "${hcloud_primary_ip.monitoring_ipv6.ip_address}1/128"
    ]
  }
  rule {
    description = "Allow Nginx Exporter from monitoring server"
    direction   = "in"
    protocol    = "tcp"
    port        = "9113"
    source_ips = [
      "${hcloud_primary_ip.monitoring_ipv4.ip_address}/32",
      "${hcloud_primary_ip.monitoring_ipv6.ip_address}1/128"
    ]
  }
}

resource "hcloud_firewall_attachment" "this" {
  firewall_id = hcloud_firewall.this.id
  server_ids = [
    hcloud_server.this.id
  ]
}

resource "hcloud_firewall" "monitoring" {
  name = var.monitoring_domain_name
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
  firewall_id = hcloud_firewall.monitoring.id
  server_ids = [
    hcloud_server.monitoring.id
  ]
}
