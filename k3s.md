root:aJPxVGUJ8DQrpR7B
k3s:pk7StwGT9qWqxQRa


# bitinflow.cloud

*.ingress.fsn1.main.bitinflow.cloud

core1.fsn1.main.bitinflow.cloud 23.88.43.164
k3s1.fsn1.main.bitinflow.cloud 88.99.38.193
k3s2.fsn1.main.bitinflow.cloud 188.34.155.83
k3s3.fsn1.main.bitinflow.cloud 188.34.159.166
k3s4.fsn1.main.bitinflow.cloud 188.34.166.9

# Install core1.fsn1.main.bitinflow.cloud

## Install MariaDB Server

```
sudo apt-get update
sudo apt-get install mariadb-server
sudo mysql_secure_installation
```

### Configure MariaDB Server

Create a new k3s user and database:

```
CREATE DATABASE k3s;
CREATE USER 'k3s'@'%' IDENTIFIED BY 'pk7StwGT9qWqxQRa';
GRANT ALL PRIVILEGES ON k3s.* TO 'k3s'@'%';
```

### Bind MariaDB Server to 0.0.0.0

```
nano /etc/mysql/mariadb.conf.d/50-server.cnf
service mysql restart
ss -ntlp | grep mysql
```

# Install k3s

## Setup DNS Records

The fixed registration address is: `k3s.fsn1.main.bitinflow.cloud`. 
It points to both A and AAAA records of the following servers: 
- `k3s1.fsn1.main.bitinflow.cloud`
- `k3s2.fsn1.main.bitinflow.cloud`

## Running Commands

```
# k3s1.fsn1.main.bitinflow.cloud
curl -sfL https://get.k3s.io | sh -s - server \
    --token tcFt7mWe7t6d3JtLcWBhmUqytwwxZsSDhxRcWR28fekGPP944v9jfhFWTYUA7fcs \
    --datastore-endpoint="mysql://k3s:pk7StwGT9qWqxQRa@tcp(core1.fsn1.main.bitinflow.cloud:3306)/k3s" \
    --node-taint CriticalAddonsOnly=true:NoExecute \
    --disable traefik

# k3s2.fsn1.main.bitinflow.cloud
curl -sfL https://get.k3s.io | sh -s - server \
    --token K1054a298b18754e09d81aec8fc3c1d323d11e6b28d04db757fa01c5e21e4194c41::server:tcFt7mWe7t6d3JtLcWBhmUqytwwxZsSDhxRcWR28fekGPP944v9jfhFWTYUA7fcs \
    --datastore-endpoint="mysql://k3s:pk7StwGT9qWqxQRa@tcp(core1.fsn1.main.bitinflow.cloud:3306)/k3s" \
    --node-taint CriticalAddonsOnly=true:NoExecute \
    --disable traefik

# k3s3.fsn1.main.bitinflow.cloud
curl -sfL https://get.k3s.io | sh -s agent --server https://k3s.fsn1.main.bitinflow.cloud:6443 \
    --token K1054a298b18754e09d81aec8fc3c1d323d11e6b28d04db757fa01c5e21e4194c41::server:tcFt7mWe7t6d3JtLcWBhmUqytwwxZsSDhxRcWR28fekGPP944v9jfhFWTYUA7fcs
    
# k3s4.fsn1.main.bitinflow.cloud
curl -sfL https://get.k3s.io | sh -s agent --server https://k3s.fsn1.main.bitinflow.cloud:6443 \
    --token K1054a298b18754e09d81aec8fc3c1d323d11e6b28d04db757fa01c5e21e4194c41::server:tcFt7mWe7t6d3JtLcWBhmUqytwwxZsSDhxRcWR28fekGPP944v9jfhFWTYUA7fcs
```

Running `kubectl get pods -A` should show the following:

```
root@k3s1:~# kubectl get pods -A
NAMESPACE     NAME                                      READY   STATUS    RESTARTS   AGE
kube-system   local-path-provisioner-5ff76fc89d-4rd6q   1/1     Running   0          12m
kube-system   coredns-7448499f4d-4wnzq                  1/1     Running   0          12m
kube-system   metrics-server-86cbb8457f-tbbws           1/1     Running   0          12m
```

## Install Nginx Ingress Controller

```
helm --kubeconfig ~/.kube/k3s upgrade --install ingress-nginx ingress-nginx \
  --repo https://kubernetes.github.io/ingress-nginx \
  --namespace ingress-nginx --create-namespace
```

```
root@k3s1:~# kubectl get pods -A
NAMESPACE       NAME                                      READY   STATUS             RESTARTS   AGE
kube-system     local-path-provisioner-5ff76fc89d-4rd6q   1/1     Running            0          33m
kube-system     coredns-7448499f4d-4wnzq                  1/1     Running            0          33m
kube-system     metrics-server-86cbb8457f-tbbws           1/1     Running            0          33m
kube-system     helm-install-ingress-nginx-c6g55          0/1     CrashLoopBackOff   7          16m
ingress-nginx   svclb-ingress-nginx-controller-786l5      2/2     Running            0          2m20s
ingress-nginx   svclb-ingress-nginx-controller-p5st2      2/2     Running            0          2m20s
ingress-nginx   svclb-ingress-nginx-controller-dtbnf      2/2     Running            0          2m20s
ingress-nginx   svclb-ingress-nginx-controller-p9zv6      2/2     Running            0          2m20s
ingress-nginx   ingress-nginx-controller-54bfb9bb-ssw87   1/1     Running            0          2m20s
```

You'll also see a `helm-install-ingress-nginx` pod in your environment. K3s uses this pod to deploy the Helm Chart and it's normal for it to be in a READY=0/1 and STATUS=Completed state once the Helm Chart has been successfully deployed. In the event your Helm Chart failed to deploy, you can view the logs of this pod to troubleshoot further.

## Get Kubectl file

```
cat /etc/rancher/k3s/k3s.yaml


chmod go-r ~/.kube/k3s

kubectl --kubeconfig ~/.kube/k3s get pods --all-namespaces
helm --kubeconfig ~/.kube/k3s ls --all-namespaces

kubectl --kubeconfig ~/.kube/k3s --namespace ingress-nginx get services -o wide -w ingress-nginx-controller
```

## Install Cert Manager

```
kubectl --kubeconfig ~/.kube/k3s apply -f https://github.com/jetstack/cert-manager/releases/download/v1.6.1/cert-manager.yaml

snap install go --classic
OS=$(go env GOOS); ARCH=$(go env GOARCH); curl -L -o cmctl.tar.gz https://github.com/jetstack/cert-manager/releases/latest/download/cmctl-$OS-$ARCH.tar.gz
tar xzf cmctl.tar.gz
sudo mv cmctl /usr/local/bin
rm cmctl.tar.gz

cmctl --kubeconfig ~/.kube/k3s check api --wait=2m
```

## Create Cluster Issuer

```yaml
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
    name: letsencrypt-staging
spec:
    acme:
        server: https://acme-staging-v02.api.letsencrypt.org/directory
        email: containers@bitinflow.com
        privateKeySecretRef:
            name: letsencrypt-staging
        solvers:
            - selector: {}
              http01:
                  ingress:
                      class: nginx
---
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
    name: letsencrypt-prod
spec:
    acme:
        server: https://acme-v02.api.letsencrypt.org/directory
        email: containers@bitinflow.com
        privateKeySecretRef:
            name: letsencrypt-prod
        solvers:
            - selector: {}
              http01:
                  ingress:
                      class: nginx
```

```
kubectl --kubeconfig ~/.kube/k3s describe certificate example-tls -n foo
```


## Debug Output
ame: nginx
    rules:
      - host: www.example.com
        http:
          paths:
            - backend:
                service:
                  name: exampleService
                  port:
                    number: 80
              path: /
    # This section is only required if TLS is to be enabled for the Ingress
    tls:
      - hosts:
        - www.example.com
        secretName: example-tls

If TLS is enabled for the Ingress, a Secret containing the certificate and key must also be provided:

  apiVersion: v1
  kind: Secret
  metadata:
    name: example-tls
    namespace: foo
  data:
    tls.crt: <base64 encoded cert>
    tls.key: <base64 encoded key>
  type: kubernetes.io/tls
```

## Deploy a Hello World Service

```
kubectl --kubeconfig ~/.kube/k3s create -f hello-world.yaml
```
