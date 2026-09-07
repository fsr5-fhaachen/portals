variable "hcloud_token" {
  description = ""
  sensitive   = true
  type        = string
}

variable "cloudflare_token" {
  description = ""
  sensitive   = true
  type        = string
}

variable "s3_region" {
  description = ""
  type        = string
  default     = "fsn1"
}

variable "s3_access_key" {
  description = ""
  sensitive   = true
  type        = string
}

variable "s3_secret_key" {
  description = ""
  sensitive   = true
  type        = string
}

variable "s3_bucket" {
  description = ""
  type        = string
  default     = "fsr5-fhaachen-portals"
}

variable "cloudflare_zone_id" {
  description = ""
  type        = string
  default     = "25b338dd8e79c6324ad1e96153b29ccd" # fsr5.de
}

variable "domain_name" {
  description = ""
  type        = string
  default     = "portals.fsr5.de"
}

variable "base_image" {
  description = ""
  type        = string
  default     = "debian-13"
}

variable "server_region" {
  description = ""
  type        = string
  default     = "fsn1"
}

variable "server_type" {
  description = ""
  type        = string
  default     = "cx23"
}

variable "monitoring_domain_name" {
  description = ""
  type        = string
  default     = "monitoring.portals.fsr5.de"
}

variable "monitoring_server_type" {
  description = ""
  type        = string
  default     = "cx23"
}

variable "grafana_admin_password" {
  description = ""
  sensitive   = true
  type        = string
}

variable "ssh_keys" {
  description = ""
  type = list(object({
    name             = string
    public_key       = string
    upload_to_hcloud = optional(bool, true)
  }))
}
