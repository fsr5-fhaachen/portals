# Deployment of Portals

This document describes how a kubernetes cluster can be created and how the portals can be deployed on it.

> [!NOTE]  
> This guide only focuses on the cluster deployment the Fachschaftsrat Elektro- und Informationstechnik uses inside Hetzner Cloud with Cloudflare DNS. It is not a general guide on how to deploy the application. Please see the [README](../README.md) for more information.

This guide will:
1. [Install prerequisites](#step-0-install-prerequisites)
2. [Setup the Hetzner Cloud project](#step-1-setup-hetzner-cloud)
3. [Create a Management Cluster for Cluster API](#step-2-create-a-management-cluster)
4. [Install Cluster API on the Management Cluster](#step-3-install-cluster-api)
5. [Create a Workload Cluster with Cluster API](#step-4-create-a-workload-cluster)
6. [Deploy Cluster Addons on the Workload Cluster](#step-5-deploy-cluster-addons)
7. [Deploy the Portals Application on the Workload Cluster](#step-6-deploy-portals)

> [!IMPORTANT]  
> The files used in this guide use placeholders. You need to copy the files and replace them with your values/secrets.

> [!WARNING]  
> You need to have good knowledge of kubernetes to follow this guide. There will be no explanation of the kubernetes basics.

## Prerequisites

To follow this guide you will need

* A linux client to work from (I would suggest using WSL2 on Windows)
* A Hetzner Cloud account with an empty project where the cluster should be deployed
* A Cloudflare account with a domain and a zone for the domain
* A personal ssh-key pair (or more keys if you want to use different keys or want to grant access for more people)

## Step 0: Install prerequisites

You will need some tools installed on your client to follow this guide. You can install them the way you want or use the following commands.

You will need:
* [hcloud-cli](https://github.com/hetznercloud/cli)
* [kubectl](https://kubernetes.io/docs/tasks/tools/install-kubectl-linux/)
* [helm](https://helm.sh/docs/intro/install/)
* [clusterctl](https://cluster-api.sigs.k8s.io/user/quick-start.html#install-clusterctl)

Optional:
* [homebrew](https://brew.sh/)
* [fzf](https://github.com/junegunn/fzf)
* [kubectx and kubens](https://github.com/ahmetb/kubectx)

```sh
# updates
sudo apt update
sudo apt upgrade -y
sudo apt install bash-completion -y

# homebrew
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
(echo; echo 'eval "$(/home/linuxbrew/.linuxbrew/bin/brew shellenv)"') >> /home/$USER/.profile
eval "$(/home/linuxbrew/.linuxbrew/bin/brew shellenv)"
cat <<EOF >> ~/.profile
if type brew &>/dev/null
then
  HOMEBREW_PREFIX="$(brew --prefix)"
  if [[ -r "${HOMEBREW_PREFIX}/etc/profile.d/bash_completion.sh" ]]
  then
    source "${HOMEBREW_PREFIX}/etc/profile.d/bash_completion.sh"
  else
    for COMPLETION in "${HOMEBREW_PREFIX}/etc/bash_completion.d/"*
    do
      [[ -r "${COMPLETION}" ]] && source "${COMPLETION}"
    done
  fi
fi
EOF

# hcloud-cli
brew install hcloud

# kubectl
sudo apt-get update
sudo apt-get install -y apt-transport-https ca-certificates curl
curl -fsSL https://pkgs.k8s.io/core:/stable:/v1.28/deb/Release.key | sudo gpg --dearmor -o /etc/apt/keyrings/kubernetes-apt-keyring.gpg
echo 'deb [signed-by=/etc/apt/keyrings/kubernetes-apt-keyring.gpg] https://pkgs.k8s.io/core:/stable:/v1.28/deb/ /' | sudo tee /etc/apt/sources.list.d/kubernetes.list
sudo apt-get update
sudo apt-get install -y kubectl
kubectl completion bash | sudo tee /etc/bash_completion.d/kubectl > /dev/null
echo "alias k=kubectl" >> ~/.bashrc
echo "complete -o default -F __start_kubectl k" >> ~/.bashrc
echo "export KUBE_EDITOR=\"nano\"" >> ~/.bashrc

# helm
curl https://baltocdn.com/helm/signing.asc | gpg --dearmor | sudo tee /usr/share/keyrings/helm.gpg > /dev/null
sudo apt-get install apt-transport-https --yes
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/helm.gpg] https://baltocdn.com/helm/stable/debian/ all main" | sudo tee /etc/apt/sources.list.d/helm-stable-debian.list
sudo apt-get update
sudo apt-get install helm
echo "source <(helm completion bash)" >> ~/.bashrc

# kubectx and kubens
sudo apt install kubectx
brew install fzf

# clusterctl
brew install clusterctl
```

## Step 1.1: Setup Hetzner Cloud

### Create API Tokens

You need to create some API tokens inside the cloud project. You can do this in the Hetzner Cloud Console under `Access > API Tokens`. You will need the following tokens:

* `cli@<YOUR_NAME>@<YOUR_CLIENT_NAME>` (used by hcloud-cli on your linux client)
* `capi@<CLUSTER_NAME>` (used by the hcloud capi controller inside the management cluster)
* `ccm@<CLUSTER_NAME>` (used by the hcloud controller manager inside the cluster)
* `csi@<CLUSTER_NAME>` (used by the hcloud csi driver inside the cluster)

You can change the names of the tokens to fit your needs. You will need to replace the names in the following commands.

> [!IMPORTANT]  
> Please save the tokens in a safe place. You will need the values in this guide and you will not be able to see them again.

### Setup hcloud-cli

You need to setup the hcloud-cli on your linux client. You can do this by using the following commands. You will need to replace the placeholders with your values.

```sh
hcloud context create <CONTEXT_NAME> # replace context name with a name of your choice (e.g. the hcloud project name)
```

The command will ask for the token you have created in the previous step.


### Upload SSH Keys

You need to upload your public ssh key to the cloud project. You can do this in the Hetzner Cloud Console under `Access > SSH Keys` or by using the following commands. You can upload multiple keys and reference them later to grant access to more people.

```sh
hcloud ssh-key create --public-key-from-file ~/.ssh/<YOUR_KEY_FILE>.pub --name <YOUR_NAME>@<YOUR_CLIENT_NAME>
```

## Step 1.2 Setup Cloudflare

### Create API Token

You need to create two API tokens for Cloudflare. You can do this in the Cloudflare Console under `My Profile > API Tokens`. You will need the following tokens:

* Zone Edit for all needed DNS Zones (for your client)
* Zone Edit for all needed DNS Zones (for cert-manager)

You can change the names of the tokens to fit your needs. You will need to replace the names in the following commands.

## Step 2: Create a Management Cluster

To use cluster api to create a workload kubernetes cluster you need to create a management cluster. This cluster will be used to deploy the cluster api components and to create the workload cluster.

In this example we will use kind to create the management cluster.

> [!WARNING]  
> Exposing kind clusters to the internet is not recommended and can cause security risks. 

### Create VM

To create a vm to run kind on you can use the following command:

```sh
hcloud server create --location nbg1 --image debian-12 --name initial-mgmt-cluster --ssh-key <YOUR_NAME>@<YOUR_CLIENT_NAME> --type cx21
```

Wait for the server to be created and then login to the server with `ssh root@<IP_ADDRESS>`.

### Setup VM and create cluster

Run the following commands on the server to create a kind kubernetes cluster:

```sh
# updates
apt update
apt upgrade -y

# install docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# install kind
[ $(uname -m) = x86_64 ] && curl -Lo ./kind https://kind.sigs.k8s.io/dl/v0.20.0/kind-linux-amd64
chmod +x ./kind
sudo mv ./kind /usr/local/bin/kind

# create cluster config. remember to replcace the placeholder
cat <<EOF > initial-mgmt-cluster.yaml
kind: Cluster
apiVersion: kind.x-k8s.io/v1alpha4
name: mgmt
networking:
  apiServerAddress: "<SERVER_IP_INITIAL_MGMT_CLUSTER_VM>"
  apiServerPort: 6443
nodes:
  - role: control-plane
  - role: worker
EOF

# create cluster
kind create cluster --config initial-mgmt-cluster.yaml
```

### Gain cluster access

Run the following commands on your local machine to copy the kubeconfig file from the server to your local machine:

```sh
# copy kubeconfig from server to local machine
scp root@<IP_ADDRESS>:/root/.kube/config ~/.kube/initial-mgmt-cluster.kubeconfig

# set currently used kubeconfig
export KUBECONFIG=~/.kube/initial-mgmt-cluster.kubeconfig

# test connection
kubectl get nodes
```

You now have an exposed kind cluster running on the server.

## Step 3: Install Cluster API

### Prepare Management Cluster

Before installing cluster api you need to do a workaround for the container images. 

Login again to the management cluster server with `ssh root@<IP_ADDRESS>` and run the following commands:

```sh
docker pull registry.k8s.io/cluster-api/kubeadm-bootstrap-controller:<YOUR_CAPI_VERSION>
kind load docker-image -n mgmt registry.k8s.io/cluster-api/kubeadm-bootstrap-controller:<YOUR_CAPI_VERSION>
docker pull registry.k8s.io/cluster-api/kubeadm-control-plane-controller:<YOUR_CAPI_VERSION>
kind load docker-image -n mgmt registry.k8s.io/cluster-api/kubeadm-control-plane-controller:<YOUR_CAPI_VERSION>
docker pull registry.k8s.io/cluster-api/cluster-api-controller:<YOUR_CAPI_VERSION>
kind load docker-image -n mgmt registry.k8s.io/cluster-api/cluster-api-controller:<YOUR_CAPI_VERSION>
```

### Install Cluster API

With the following commands you will install cluster api on the management cluster. 

> [!IMPORTANT]
> Make sure that you have selected the right kubernetes cluster (maybe check with `kubectx`)

```sh
clusterctl init --core cluster-api --bootstrap kubeadm --control-plane kubeadm --infrastructure hetzner
```

You can check if the installation was successful by running `kubectl get pods -A`. You should see pods in the `caph-system`, `capi-system`, `capi-kubeadm-bootstrap-system` and `capi-kubeadm-control-plane-system` namespace. 

## Step 4: Create a Workload Cluster

### Create Cluster

Run the following commands to create a workload cluster:

> [!NOTE]  
> You will need to replace some values base64 encoded. You can use `echo -n "<VALUE>" | base64 -w 0` to encode the values.

```sh
# replace placeholders before applying
kubectl apply -f cluster/
```

### Create Infrastructure beneath Cluster

After the `HetznerCluster` object is ready (you can verify this with `k get hetznercluster <CLUSTER_NAME>`) you have to run the following commands:

```sh
# create nat gateway
hcloud network add-route <CLUSTER_NAME> --destination 0.0.0.0/0 --gateway 10.0.255.254
hcloud server create --location fsn1 --image debian-12 --name <CLUSTER_NAME>-nat-gateway --placement-group <CLUSTER_NAME>-gw --ssh-key <YOUR_NAME>@<YOUR_CLIENT_NAME> --type cx11 --user-data-from-file ./nat-gateway/cloud-config.yaml
hcloud server attach-to-network -n <CLUSTER_NAME> --ip 10.0.255.254 <CLUSTER_NAME>-nat-gateway

# create dns records
curl --request POST --url https://api.cloudflare.com/client/v4/zones/<CLOUDFLARE_ZONE_ID>/dns_records --header 'Content-Type: application/json' --header 'X-Auth-Key: <YOUR_CLOUDFLARE_API_TOKEN>' --data '{"content": "<IP_OF_API_LOADBALANCER>", "name": "<CLUSTER_API_URL>", "proxied": false, "type": "A", "comment": "Kubernetes API", "tags": [], "ttl": 3600}'
```

### Deploy CNI and CCM

To finish the cluster setup you need to deploy the CNI (container network interface) and the CCM (cloud controller manager). You can do this by running the following commands:

```sh
# cilium (cni)
helm repo add cilium https://helm.cilium.io/
helm upgrade --install cilium cilium/cilium --namespace cilium -f deployments/cilium-values.yaml # remember to replace the placeholders

# ccm
kubectl apply -f deployments/ccm-secret.yaml
helm repo add hcloud https://charts.hetzner.cloud
helm upgrade --install ccm hcloud/hcloud-cloud-controller-manager -n ccm -f deployments/ccm-values.yaml
```

### Wait for Cluster to be ready

After deploying the csi and ccm you have to wait for all nodes to come up. You can watch the process with `watch kubectl get nodes,pods -A`. 

## Step 5: Deploy Cluster Addons

In this step you will deploy the addons to the cluster. You can do this by running the following commands.

This will install:
* hcloud csi (container storage interface)
* metrics server
* nginx ingress
* cert-manager
* postgresql cluster
* redis cluster
* monitoring
* logging

```sh
# csi (container storage interface)
kubectl apply -f deployments/addons/csi-secret.yaml
kubectl apply -f deployments/addons/csi-2.7.0.yaml

# metrics server
helm repo add metrics-server https://kubernetes-sigs.github.io/metrics-server/
helm upgrade --install metrics-server metrics-server/metrics-server --namespace metrics-server --create-namespace

# ingress
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm upgrade --install ingress-nginx ingress-nginx/ingress-nginx --namespace ingress-nginx --create-namespace -f deployments/addons/ingress-nginx-values.yaml

# cert-manager
helm repo add jetstack https://charts.jetstack.io
helm upgrade --install cert-manager jetstack/cert-manager --namespace cert-manager --create-namespace -f deployments/addons/cert-manager-values.yaml
kubectl apply -f deployments/addons/cert-manager-issuer.yaml

# postgresql operator
helm repo add cnpg https://cloudnative-pg.github.io/charts
helm upgrade --install cnpg cnpg/cloudnative-pg --namespace cnpg --create-namespace

# redis operator
helm repo add ot-helm https://ot-container-kit.github.io/helm-charts/
helm upgrade --install redis-operator ot-helm/redis-operator --namespace redis --create-namespace
```

<!-- TODO: Add monitoring -->

<!-- TODO: Add logging -->

<!-- TODO: Configure addons -->

<!-- TODO: Add horizontal and vertical autoscaler -->

## Step 6: Deploy Portals

### Deploy Portals

In this step you will deploy the portals application, a PostgreSQL database and a redis cluster. You can do this by running the following commands.

Remember to replace the placeholders in the values files with your values.

```sh
# create namespace
kubectl create namespace portals

# postgresql cluster
kubectl apply -f deployments/portals/pgsql.yaml

# redis cluster
kubectl apply -f deployments/portals/redis-pw-secret.yaml
helm repo add ot-helm https://ot-container-kit.github.io/helm-charts/
helm upgrade --install portals-redis ot-helm/redis-cluster --namespace portals -f deployments/portals/redis-values.yaml

# portals
helm repo add portals https://fsr5-fhaachen.github.io/portals/
helm upgrade --install portals portals/portals --namespace portals -f deployments/portals/portals-values.yaml
```

### Setup Portals

To fully setup portals you need to seed the database. You can do it by executing the following command in one portals pod. You can exec into the pod with `kubectl exec -it <POD_NAME> -n portals -- sh`.

After switching into the pod, execute the following command to seed the db:

```sh
php artisan migrate:fresh --seed
```

### Setup DNS

To setup the dns records for portals you need to create a dns record for the ingress. You can do this by running the following command:

```sh
# wildcard record for ingress
curl --request POST --url https://api.cloudflare.com/client/v4/zones/<CLOUDFLARE_ZONE_ID>/dns_records --header 'Content-Type: application/json' --header 'X-Auth-Key: <YOUR_CLOUDFLARE_API_TOKEN>' --data '{"content": "<IP_OF_INGRESS_LOADBALANCER>", "name": "*.<YOUR_INGRESS_IP>", "proxied": false, "type": "A", "comment": "Kubernetes Ingress", "tags": [], "ttl": 3600}'

# record for portals (only if not inside ingress wildcard)
curl --request POST --url https://api.cloudflare.com/client/v4/zones/<CLOUDFLARE_ZONE_ID>/dns_records --header 'Content-Type: application/json' --header 'X-Auth-Key: <YOUR_CLOUDFLARE_API_TOKEN>' --data '{"content": "<ONE_OF_THE_WILDCARD_DOMAINS_BEFORE>", "name": "<YOUR_PORTALS_DOMAIN>", "proxied": false, "type": "CNAME", "comment": "Kubernetes Ingress Portals", "tags": [], "ttl": 3600}'
```

Ready, you can connect to portals on your configured url.
