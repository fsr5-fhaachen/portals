import os
import requests
from dotenv import load_dotenv
from hcloud import Client

load_dotenv()
client = Client(token=os.getenv("HETZNER_CLOUD_TOKEN"))

def find_next_server_id():
    print("searching for next server id...")
    highest_number = -1
    for server in client.servers.get_all():
        curr_numb = server.name.replace("portals-", "")
        if curr_numb.isnumeric():
            curr_numb = int(curr_numb)
            print("found server with id " + str(curr_numb))
            if curr_numb > highest_number:
                highest_number = curr_numb
    print("highest server id is " + str(highest_number))
    return highest_number + 1

def setup_server(server_id):
    print("creating server from snapshot...")

    keys = []
    for keyname in os.getenv("SSH_KEY_NAMES").split(";"):
        keys.append(client.ssh_keys.get_by_name(keyname))

    response = client.servers.create(name="portals-" + str(server_id), 
                                    server_type=client.server_types.get_by_name("cpx11"),
                                    image=client.images.get_by_id(os.getenv("IMAGE_ID")),
                                    ssh_keys=keys,
                                    volumes=[],
                                    firewalls=[],
                                    networks=[client.networks.get_by_id(os.getenv("NETWORK_ID"))],
                                    user_data="",
                                    labels={"portals-role": "webhost"},
                                    datacenter=client.datacenters.get_by_id(4),
                                    start_after_create=True,
                                    automount=None,
                                    placement_group=client.placement_groups.get_by_id(os.getenv("PLACEMENTGROUP_ID")))

    print("Created server " + response.server.name)

    return {"ipv4": response.server.public_net.ipv4.ip, "ipv6": response.server.public_net.ipv6.ip.replace("::/64", "::1")}

def setup_cloudflare(id, ips):
    print("creating dns records...")
    requests.post("https://api.cloudflare.com/client/v4/zones/" + os.getenv("CLOUDFLARE_ZONEID") + "/dns_records", headers={"X-Auth-Email": os.getenv("CLOUDFLARE_EMAIL"), "X-Auth-Key": os.getenv("CLOUDFLARE_TOKEN")}, json={"type":"A","name":"portals-" + str(id),"content": ips.get("ipv4"), "ttl":1,"proxied":False})
    requests.post("https://api.cloudflare.com/client/v4/zones/" + os.getenv("CLOUDFLARE_ZONEID") + "/dns_records", headers={"X-Auth-Email": os.getenv("CLOUDFLARE_EMAIL"), "X-Auth-Key": os.getenv("CLOUDFLARE_TOKEN")}, json={"type":"AAAA","name":"portals-" + str(id),"content": ips.get("ipv6"), "ttl":1,"proxied":False})
    print("dns records finished")

def add_to_local_ansible(id):
    os.system("echo -e 'portals-" + str(id) + ".fsr5.de\n' >> $(pwd)/hosts.ini")
    print("added server to local ansible hosts file")

def main():
    print("starting scaleup...")
    next_id = find_next_server_id()
    ips = setup_server(next_id)
    setup_cloudflare(next_id, ips)
    add_to_local_ansible(next_id)
    print("scaleup complete!")
    

if __name__ == "__main__":
    main()