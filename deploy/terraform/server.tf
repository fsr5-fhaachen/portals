resource "hcloud_server" "this" {
  name        = var.domain_name
  image       = var.base_image
  server_type = var.server_type
  datacenter  = local.hcloud.datacenter_from_location[var.server_region]
  ssh_keys = concat(
    [
      hcloud_ssh_key.this.id
    ],
    [
      for key in var.ssh_keys : (
        key.upload_to_hcloud ? hcloud_ssh_key.user_upload[key.name].id : data.hcloud_ssh_key.already_uploaded[key.name].id
      )
    ]
  )
  public_net {
    ipv4_enabled = true
    ipv4         = hcloud_primary_ip.ipv4.id
    ipv6_enabled = true
    ipv6         = hcloud_primary_ip.ipv6.id
  }
  provisioner "remote-exec" {
    connection {
      host        = self.ipv4_address
      port        = 22
      user        = "root"
      private_key = tls_private_key.ssh_key.private_key_pem
    }
    inline = [
      "echo \"Server reachable via SSH.\""
    ]
  }
  lifecycle {
    precondition {
      condition     = contains(local.hcloud.validations.server_type, var.server_type)
      error_message = "The server type \"${var.server_type}\" is not available in the Hetzner Cloud. Available types: ${join(", ", local.hcloud.validations.server_type)}"
    }
  }
}
