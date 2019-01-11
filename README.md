# Quantum_Resistant_Ledger-Faucet

This is the software running the QRL Faucet hosted over at https://faucet-qrl.tips configured to give away coins once a day to any valid QRL address.

The faucet interfaces with the gRPC wallet running on a full node server side. The WalletAPI has been developed to utilize slave transactions by default.

Please see below for installation instructions if you want to host a faucet your self.

> This software is provided to the public AS-IS with no guarantee. Please contribute if you find an issue with the faucet.

## Overview

The server is broken up into a few parts to simplify the operation and security. There is extensive setup and configuration that must be completed before this will work, and is no way a simple setup.


### Web Server

The site is built as a static php/HTML site that can be hosted from any modern web server. I chose apache2 as it is most familiar to me. Nginx would be another option.

Installation and configuration of a web server is out of scope for these instructions.

### QRL Node
This is required to transact on the QRL network. You will need to sync a full node.

### Scripting

There are a few scripts that this faucet relies on. Most live in the `/script` directory however the Web server needs to have access to the php scripts so they live in the web root.

#### PHP

The `/web/php/` directory contains getInfo.php and main.php.


`main.php` is the script that the user will `$POST` to. It collects the QRL address, time submitted, IP address *(hashed)* and commits it to the mySQL database. 

> At the top of the `main.php` file are user configurable settings that must be configured for the faucet to work.

See below for configuration details.

`getInfo.php` is used to gather information from the user that submitted the request for QRL. This grabs the submitted IP address and verifies it has not been spoofed along the way. getInfo.php is called by the main.php script to validate an IP.

We accept a POST from the website to enter a valid QRL address into the database. The 

### Database

MySQL database is used to store and track the faucet operations.

## Setup

This instruction assumes a clean installation of Ubuntu 16.04. You will want to set this up on a reliable server connected to a stable network connection with a static IP address for simplicity.

## Installation


1. Connect to and update server
	1. VPS setup with Ubuntu 16.04 
	2. Update and Upgrade the server to latest
 
2. Install packages
	1. Web Server, Database, Python 3.5,  
	2. Get software, wallet-rest-proxy, faucet, 
	3. Install qrl
 
3. Start QRL Node
 	1. Fully sync the node
 	2. Wallet setup
	
4. DNS
 	1. Set Hostname and FQDN on server
 	2. Cloudflare
		1. CDN Setup
		2. [Mod_Cloudflare Install](https://www.cloudflare.com/technical-resources/#mod_cloudflare) for real ip addresses

5. Database
	1. Setup Database
	2. User and password
	3. Table and grant privileges

6. Web Server(Apache2)
	1. Certificate
	2. Setup sites available
	3. Move files to web DIR
	4. permissions and owners
	5. apache password for ADMIN site / dashboard

7. Configure Faucet

8. Captcha
	1. Coinhive Captcha setup

9. Hardening
 	1. Firewall

### 1.) Install packages

The faucet requires a web server, a database, PHP, Python and a few other random packages to be installed into your OS. 

```bash
sudo apt-get install -y screen git apache2 curl mysql-server php libapache2-mod-php php-mcrypt php-mysql python3-pip swig3.0 python3-dev build-essential cmake pkg-config libssl-dev libffi-dev libhwloc-dev libboost-dev fail2ban jq 
```

This will prompt you to set a password for the root mysql user. make this very difficult to guess, and ensure you have recorded the password somewhere safe.


#### c) Software


**QRL**

Install QRL and sync the node. 

```bash
sudo apt-get -y install swig3.0 python3-dev python3-pip build-essential cmake pkg-config libssl-dev libffi-dev libhwloc-dev libboost-dev

pip3 install -U setuptools

pip3 install -U qrl
```


**Grab the state** *\*Optional*

I have a hosted repository located at https://github.com/fr1t2/QRL-Nightly-Chain that can be used to speed up the syncing process significantly. After you have followed the instructions over there start tthe node and it should sync in a short time.

**Start QRL**

```bash
screen -dm start_qrl
```

**qrl_walletd**

Start the wallet daemon provided with the QRL package.

```bash
qrl_walletd
```

This process runs in the background.

---


**GoLANG**

Install instructions from the golang [install instructions](https://golang.org/doc/install)

[Download](https://golang.org/dl/) the linux archive and extract it into /usr/local, creating a Go tree in /usr/local/go. For example:

`tar -C /usr/local -xzf go$VERSION.$OS-$ARCH.tar.gz` 

(Typically these commands must be run as root or through sudo.)

Add /usr/local/go/bin to the PATH environment variable. You can do this by adding this line to your /etc/profile (for a system-wide installation) or $HOME/.profile:

Setup your GOPATH

`export GOPATH=$HOME/go`

Grab the walletAPI Golang repo

`go get github.com/theQRL/walletd-rest-proxy`

`cd $GOPATH/src/github.com/theQRL/walletd-rest-proxy`


`go build`
 This builds the latest wallet-rest-proxt to interface with the QRL's grpc system.

**Start the API**

Start the API in a screen or background process. You must call out the location of the go install or change to that directory.

```bash
cd $GOPATH/src/github.com/theQRL/walletd-rest-proxy

screen -d -m ./walletd-rest-proxy -serverIPPort 127.0.0.1:5359 -walletServiceEndpoint 127.0.0.1:19010
```

This will leave the proxy open accepting connections from localhost.

**Create QRL wallet**

Using the walletAPI to create a wallet with slaves, enter the following after you have started the walletAPI

```bash
curl -XPOST http://127.0.0.1:5359/api/AddNewAddressWithSlaves
```

This will create a wallet.json file in your .qrl directory with multiple slaves.

Check your address with 

```bash
curl -XGET http://127.0.0.1:5359/api/ListAddresses
```

---

**Faucet**

Grab the latest code for this repository by cloning the faucet

```bash
cd ~/ && git clone https://github.com/fr1t2/Quantum_Resistant_Ledger-Faucet.git
```
This will clone the faucet into your users $HOME directory

---

### Setup Database

We need to create the faucet database and add a table to track payments

Connect to the mysql server using the root user and password you setup on install.

```bash
mysql -u root -p
```

In the mySQL prompt you will see `mysql>` at the beginning of the command prompt. Enter the following to setup a database with the PAYOUT table. Please use a new passphrase that is difficult to guess.

```bash
CREATE DATABASE faucet;
CREATE USER 'qrl'@'localhost' IDENTIFIED BY 'Some_Random_Password_Here';
GRANT ALL PRIVILEGES ON faucet . * TO 'qrl'@'localhost';
USE faucet;
CREATE TABLE PAYOUT  (TX_ID VARCHAR(200), QRL_ADDR VARCHAR(100), IP_ADDR VARCHAR(255), PAYOUT DECIMAL(11,8), DATETIME DATETIME);
FLUSH PRIVILEGES;
exit

```

> Important make sure you record the user and passphrase you use for the database. These will need to be put into the scripts later.

### Configure the Site

Now that all of the install is done, we can configure the site. The main files are located in the `web` directory. Copy this into the webroot of your web server. First clean out any junk that may be in there. We also need to move the script folder to a location where the scripts can be found. Make sure this is outside of the web root for security. Don't let apache access this folder! 

```bash
# Remove any old web files that may be in there
sudo rm -r /var/www/html/*
# Move all web files over
sudo cp ~/QRL-Faucet/web/* 
# change owner to web server
sudo chown -R www-data:www-data /var/www/html/
# Copy the script folder into a known location for the scripts to run.
sudo cp -r ~/QRL-Faucet/script /var/www/
```

With the scripts and web files moved over the last thing is to edit the config files to reflect your installation.

Change the following:

**/var/www/html/php/main.php**

```bash
$payoutInterval = 24; #payout interval in hours before user is valid again
$SQLservername = "localhost"; # database host
$SQLuser = "qrl"; # Database user
$SQLpassword = "YOUR_PASSWORD_HERE"; # Database password
$database = "faucet"; # Database Name
$coinhiveSecret = "YOUR_COINHIVE_SECRET_HERE"; #Coinhive Secret key
$hashes = 256; # Amount of hashes before valid, must match index.php
```

**/var/www/script/payout.py**

```bash
host = "localhost" # the location of the database
user = "qrl" # Database user
passwd = "YOUR_PASSWORD_HERE" #Database password
database = "faucet" # Name of database
payoutTime = 1 	# Hours to look back for addresses to pay 
payNumber = 100 # Maximum number of QRL addresses allowed in a TX
fee = 10 # Quanta in shor (fee*10^9=quanta)
amountToPay = 100 # Quanta in shor (amountToPay*10^9=quanta)
```

**/var/www/index.php**

```bash
data-hashes ="256" #Number of hashes before validation, Must match php/main.php
data-key ="YOUR_COINHIVE_PUBLIC_KEY_HERE" #Coinhive Public Key
```

### Automate 

Setup the faucet server to automatically send transactions when the time matches the settings in the `payout.py` script. This will run the script at that interval and search the database for addresses that match the time, and payout. If these numbers don't match you will payout more than you intend.

**Cron Job**

Edit the cron file with:
```bash
crontab -e
```

at the bottom of this file add
`0 * * * * /home/$USER/QRL-Faucet/script/payout.py`

Change the name and location to suit. The user who's crontab you are editing must have execute rights on that file. To change the schedule of payouts checkout [crontab.guru](https://crontab.guru) for more help with cron.

## Finish Up

Fund the address printed in the site by clicking on the QRL symbol on bottom.

Test your faucet out by entering your address and see if you are paid in about an hour. If so you are ready to give away QRL!

## Script Installation

Working to script this out into a full on install script. Coming soon!!




# Issues

If you run into any issues please submit an issue to the repository.