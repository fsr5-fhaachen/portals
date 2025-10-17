terraform {
  required_version = "~> 1.10.5"
  required_providers {
    hcloud = {
      source  = "hetznercloud/hcloud"
      version = "~> 1.54.0"
    }
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 5.11.0"
    }
    minio = {
      source  = "aminueza/minio"
      version = "~> 3.8.0"
    }
    tls = {
      source  = "hashicorp/tls"
      version = "~> 4.1.0"
    }
    local = {
      source  = "hashicorp/local"
      version = "~> 2.5.3"
    }
  }
}

provider "hcloud" {
  token = var.hcloud_token
}

provider "cloudflare" {
  api_token = var.cloudflare_token
}

provider "minio" {
  minio_server   = "${var.s3_region}.your-objectstorage.com"
  minio_region   = var.s3_region
  minio_user     = var.s3_access_key
  minio_password = var.s3_secret_key
  minio_ssl      = true
}
