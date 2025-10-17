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
}

resource "hcloud_firewall_attachment" "this" {
  firewall_id = hcloud_firewall.this.id
  server_ids = [
    hcloud_server.this.id
  ]
}
