data "hcloud_datacenters" "all" {}

data "hcloud_server_types" "all" {}

locals {
  hcloud = {
    datacenter_from_location = {
      for dc in data.hcloud_datacenters.all.datacenters : dc.location.name => dc.name
    }
    validations = {
      server_type = [for type in data.hcloud_server_types.all.server_types : type.name]
    }
  }
}
