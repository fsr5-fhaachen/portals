resource "hcloud_primary_ip" "ipv4" {
  name        = "${var.domain_name}-ipv4"
  location    = var.server_region
  type        = "ipv4"
  auto_delete = false # needed for terraform destroy
}

resource "hcloud_primary_ip" "ipv6" {
  name        = "${var.domain_name}-ipv6"
  location    = var.server_region
  type        = "ipv6"
  auto_delete = false # needed for terraform destroy
}

resource "hcloud_rdns" "ipv4" {
  primary_ip_id = hcloud_primary_ip.ipv4.id
  ip_address    = hcloud_primary_ip.ipv4.ip_address
  dns_ptr       = var.domain_name
}

resource "hcloud_rdns" "ipv6" {
  primary_ip_id = hcloud_primary_ip.ipv6.id
  ip_address    = "${hcloud_primary_ip.ipv6.ip_address}1"
  dns_ptr       = var.domain_name
}

resource "hcloud_primary_ip" "monitoring_ipv4" {
  name        = "${var.monitoring_domain_name}-ipv4"
  location    = var.server_region
  type        = "ipv4"
  auto_delete = false # needed for terraform destroy
}

resource "hcloud_primary_ip" "monitoring_ipv6" {
  name        = "${var.monitoring_domain_name}-ipv6"
  location    = var.server_region
  type        = "ipv6"
  auto_delete = false # needed for terraform destroy
}

resource "hcloud_rdns" "monitoring_ipv4" {
  primary_ip_id = hcloud_primary_ip.monitoring_ipv4.id
  ip_address    = hcloud_primary_ip.monitoring_ipv4.ip_address
  dns_ptr       = var.monitoring_domain_name
}

resource "hcloud_rdns" "monitoring_ipv6" {
  primary_ip_id = hcloud_primary_ip.monitoring_ipv6.id
  ip_address    = "${hcloud_primary_ip.monitoring_ipv6.ip_address}1"
  dns_ptr       = var.monitoring_domain_name
}
