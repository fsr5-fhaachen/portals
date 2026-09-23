resource "local_file" "ansible_inventory" {
  filename = "${path.module}/../ansible/inventory.yaml"
  content = yamlencode({
    all = {
      children = {
        app = {
          hosts = {
            "${var.domain_name}" = {
              ansible_host = hcloud_primary_ip.ipv4.ip_address
            }
          }
        }
        monitoring = {
          hosts = {
            "${var.monitoring_domain_name}" = {
              ansible_host = hcloud_primary_ip.monitoring_ipv4.ip_address
            }
          }
        }
      }
    }
  })
}

resource "local_sensitive_file" "ansible_variables" {
  filename = "${path.module}/../ansible/vars_terraform.yaml"
  content = yamlencode({
    s3_endpoint            = "https://${var.s3_region}.your-objectstorage.com"
    s3_region              = var.s3_region
    s3_access_key          = var.s3_access_key
    s3_secret_key          = var.s3_secret_key
    s3_bucket              = var.s3_bucket
    domain_name            = var.domain_name
    monitoring_domain_name = var.monitoring_domain_name
    app_server_ip          = hcloud_primary_ip.ipv4.ip_address
    grafana_admin_password = var.grafana_admin_password
  })
}

resource "local_sensitive_file" "ansible_ssh_key" {
  filename = "${path.module}/../ansible/ssh-key.pem"
  content  = tls_private_key.ssh_key.private_key_openssh
}
