# Deployment

This repo contains an automated way to deploy the application to a [Hetzner Cloud](https://hetzner.cloud/?ref=QVP9EsLHwtNY) server using [Terraform](https://developer.hashicorp.com/terraform) or [OpenTofu](https://opentofu.org/) and [Ansible](https://docs.ansible.com/).

## Prerequisites

- A Hetzner Cloud account. You can create one [here](https://hetzner.cloud/?ref=QVP9EsLHwtNY).
- A Hetzner Cloud API token. You can create them in the [Hetzner Cloud Console](https://hetzner.cloud/?ref=QVP9EsLHwtNY).
- Hetzner Cloud S3 credentials. You can create them in the [Hetzner Cloud Console](https://hetzner.cloud/?ref=QVP9EsLHwtNY).
- A Cloudflare account with the domain connected to it.
- A Cloudflare API token with permissions to manage DNS records. You can create them in the [Cloudflare Dashboard](https://dash.cloudflare.com/).
- [Terraform](https://developer.hashicorp.com/terraform) or [OpenTofu](https://opentofu.org/) installed on your local machine.
- [Ansible](https://docs.ansible.com/) installed on your local machine.

## Variables

You have to copy the `terraform/this.auto.tfvars.example` file to `terraform/this.auto.tfvars` and fill in the required variables.

Also you have to copy the `ansible/vars.yaml.example` file to `ansible/vars.yaml` and fill in the required variables.

## Deployment

1. Navigate to the `./terraform` directory.
2. Run `terraform init` or `tofu init` to initialize the Terraform providers.
3. Run `terraform apply` or `tofu apply` to create the server and other resources.
4. After the Terraform run is complete, navigate to the `./ansible` directory.
5. Run `ansible-playbook playbook.yaml` to configure the server.
6. After the Ansible playbook is complete, your application should be accessible via the domain you specified in the variables.

## Cleanup

To destroy the created resources, navigate to the `terraform` directory and run `terraform destroy` or `tofu destroy`.

## Notes

If `ansible-playbook` fails in the DevContainer, try running `export LANG=C.UTF-8` before.
