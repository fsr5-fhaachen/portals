data "hcloud_server_types" "all" {}

locals {
  hcloud = {
    validations = {
      server_type = [for type in data.hcloud_server_types.all.server_types : type.name]
    }
  }
}
