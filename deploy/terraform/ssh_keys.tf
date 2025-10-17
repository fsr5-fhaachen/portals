resource "tls_private_key" "ssh_key" {
  algorithm = "ED25519"
}

resource "hcloud_ssh_key" "this" {
  name       = var.domain_name
  public_key = tls_private_key.ssh_key.public_key_openssh
}

resource "hcloud_ssh_key" "user_upload" {
  for_each   = { for key in var.ssh_keys : key.name => key if key.upload_to_hcloud }
  name       = each.value.name
  public_key = each.value.public_key
}

data "hcloud_ssh_key" "already_uploaded" {
  for_each = { for key in var.ssh_keys : key.name => key if !key.upload_to_hcloud }
  name     = each.value.name
}
