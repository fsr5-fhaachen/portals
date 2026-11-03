data "hcloud_server_types" "all" {}

locals {
  monitoring_exporters = {
    "Node Exporter"     = "9100"
    "Postgres Exporter" = "9187"
    "Redis Exporter"    = "9121"
    "Nginx Exporter"    = "9113"
  }
  hcloud = {
    validations = {
      server_type = [for type in data.hcloud_server_types.all.server_types : type.name]
    }
  }
}
