# Portals Helm Chart

This chart deploys the Portals application to a Kubernetes cluster.

## Install

You can install the chart with the following command:

```sh
helm repo add portals https://fsr5-fhaachen.github.io/portals/
helm upgrade --install portals portals/portals --namespace portals --create-namespace -f values.yaml
```

## Values

You can find the default values in the [values.yaml](values.yaml) file.

You can override the default values but there are some values that need to be changed. The (minimum) required values are:

```yaml
environment: 
  APP_NAME: Erstiwoche FB5
  APP_KEY: # insert app key here
  APP_URL: https://portals.fsr5.de
  TUTOR_PASSWORD: password # insert secret password here
  ADMIN_PASSWORD: admin # insert secret password here
  DB_CONNECTION: pgsql
  DB_HOST: # insert db host here
  DB_PORT: "5432"
  DB_DATABASE: postgres
  DB_USERNAME: postgres
  DB_PASSWORD: # insert db password here
  REDIS_HOST: # insert redis host here
  REDIS_PASSWORD: # insert redis password here
  REDIS_PORT: "6379"
ingress:
  enabled: true
  className: "nginx"
  annotations:
    cert-manager.io/issuer: "letsencrypt-prod"
  hosts:
    - portals.fsr5.de
  tls: true
```
