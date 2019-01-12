# QRL-Faucet

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/9e84757acf4144f19e818a5b2a698a0a)](https://app.codacy.com/app/fr1t2/Quantum_Resistant_Ledger-Faucet?utm_source=github.com&utm_medium=referral&utm_content=fr1t2/Quantum_Resistant_Ledger-Faucet&utm_campaign=Badge_Grade_Settings)

This is the software running the QRL Faucet hosted over at https://faucet.qrl.tips configured to give away coins once a day to any valid QRL address.

The faucet interfaces with the gRPC wallet running on a full node server side. The WalletAPI has been developed to utilize slave transactions by default.

Please see below for installation instructions if you want to host a faucet your self.

> This software is provided to the public AS-IS with no guarantee. Server hardening and best practice is recommended.

## Overview

The server is broken up into a few parts to simplify the operation and security. There is extensive setup and configuration that must be completed before this will work, and is no way a simple setup.

The site is built as a static php/HTML site that can be hosted from any modern web server. I chose apache2 as it is most familiar to me. Nginx would be another option.

Installation and configuration of a web server is out of scope for these instructions.

### QRL Node

This is required to transact on the QRL network. You will need to sync a full node.

### Scripting

There are a few scripts that this faucet relies on. Most live in the `/script` directory however the Web server needs to have access to the php scripts so they live in the web root.

### PHP

The `/web/php/` directory contains getInfo.php and main.php.

`main.php` is the script that the user will `$POST` to. It collects the QRL address, time submitted, IP address *(hashed)* and commits it to the mySQL database. 

> At the top of the `main.php` file are user configurable settings that must be configured for the faucet to work.

See below for configuration details.

`/web/php/getInfo.php` is used to gather information from the user that submitted the request for QRL. This grabs the submitted IP address and verifies it has not been submitted within the last 24 hrs. `/web/php/getInfo.php` is called by the `/web/php/main.php` script to validate an IP.

We accept a POST from the website to enter a valid QRL address and hashed IP address into the database with a time stamp.

### Database

MySQL database is used to store and track the faucet operations. Instructions can be found below


## Installation

This instruction assumes a clean installation of Ubuntu 16.04. You will want to set this up on a reliable server connected to a stable network connection with a static IP address for simplicity.

1. [Install Packages](#1---install-packages)
2. [Install Software](#2---install-software)
   1. [QRL](#qrl)
   2. [QRL State](#qrl-state)
   3. [GoLang](#golang)
   4. [Faucet](#faucet)
3. [Config](#3---config)
   1. [Start QRL](#start-qrl)
   2. [start qrl_walletd](#start-qrl_walletd)
   3. [Start The API](#start-the-api)
   4. [Create Wallet](#create-qrl-wallet)
   5. [Setup Database](#setup-database)
   6. [COnfigure The Site](#configure-the-site)
4. [Automate](#4---sutomate)
   1. [Cron Job](#cron-job)
5. [Finish Up](#5---finish-up)

**Basic Install Process Outline**

- Start QRL Node
   - Fully sync the node
- Wallet setup
- DNS
   - Set Hostname and FQDN on server
   - Cloudflare
   - CDN Setup
   - [Mod_Cloudflare Install](https://www.cloudflare.com/technical-resources/#mod_cloudflare) for real IP addresses
- Database
   - Setup Database
   - User and password
   - Table and grant privileges
- Web Server(Apache2)
   - Certificate
   - Setup sites available
   - Move files to web DIR
   - permissions and owners
   - apache password for ADMIN site / dashboard
- Configure Faucet
   - Script Config
- Captcha
   - Coinhive Captcha setup
- Hardening
   - Firewall

### \#1 - Install packages

```bash
sudo apt-get install -y screen git apache2 curl mysql-server php libapache2-mod-php php-mcrypt php-mysql python3-pip swig3.0 python3-dev build-essential cmake pkg-config libssl-dev libffi-dev libhwloc-dev libboost-dev fail2ban jq 
```

This will prompt you to set a password for the root MySQL user. make this very difficult to guess, and ensure you have recorded the password somewhere safe.


### \#2 - Install Software

#### QRL

```bash
sudo apt-get -y install swig3.0 python3-dev python3-pip build-essential cmake pkg-config libssl-dev libffi-dev libhwloc-dev libboost-dev

pip3 install -U setuptools

pip3 install -U qrl
```

#### QRL state

> \*Optional

I have a hosted repository located at [github.com/fr1t2/QRL-Nightly-Chain](https://github.com/fr1t2/QRL-Nightly-Chain) that can be used to speed up the syncing process significantly. 

After you have followed the instructions over there start the node and it should sync in a short time.

#### GoLANG

Install instructions from the golang [install instructions](https://golang.org/doc/install)

[Download](https://golang.org/dl/) the Linux archive and extract it into /usr/local, creating a Go tree in /usr/local/go. For example:

`tar -C /usr/local -xzf go$VERSION.$OS-$ARCH.tar.gz` 

(Typically these commands must be run as root or through sudo.)

Add /usr/local/go/bin to the PATH environment variable. You can do this by adding this line to your /etc/profile (for a system-wide installation) or $HOME/.profile:

Setup your GOPATH

`export GOPATH=$HOME/go`

Grab the walletAPI Golang repo

`go get github.com/theQRL/walletd-rest-proxy`

`cd $GOPATH/src/github.com/theQRL/walletd-rest-proxy`


`go build`
 This builds the latest wallet-rest-proxy to interface with the QRL's grpc system.


#### Faucet

Grab the latest code for this repository by cloning the faucet

```bash
cd ~/ && git clone https://github.com/fr1t2/QRLr-Faucet.git
```
This will clone the faucet into your users $HOME directory

### \#3 - Config


#### Start QRL 

```bash
screen -dm start_qrl
```

#### start qrl_walletd

Start the wallet daemon provided with the QRL package.

```bash
qrl_walletd
```

This process runs in the background.

#### Start the API

Start the API in a screen or background process. You must call out the location of the go install or change to that directory.

```bash
cd $GOPATH/src/github.com/theQRL/walletd-rest-proxy

screen -d -m ./walletd-rest-proxy -serverIPPort 127.0.0.1:5359 -walletServiceEndpoint 127.0.0.1:19010
```

This will leave the proxy open accepting connections from localhost on port 5359.

#### Create QRL wallet

Using the walletAPI to create a wallet with slaves, enter the following after you have started the walletAPI

```bash
curl -XPOST http://127.0.0.1:5359/api/AddNewAddressWithSlaves
```

This will create a wallet.json file in your .qrl directory with multiple slaves.

Check your address with 

```bash
curl -XGET http://127.0.0.1:5359/api/ListAddresses
```

You will want to backup the wallet private seed. 

```bash
curl -XPOST http://127.0.0.1:5359/api/GetRecoverySeeds -d '
{
  "address": "YOUR_QRL_ADDRESS_FROM_ABOVE_HERE"
}'
```

Save the output some where safe.

#### Setup Database

We need to create the faucet database and add a table to track payments

Connect to the mysql server using the root user and password you setup on install.

```bash
mysql -u root -p
```

Enter the following to setup a database with the PAYOUT table. 

```sql
CREATE DATABASE faucet;
CREATE USER 'qrl'@'localhost' IDENTIFIED BY 'Some_Random_Password_Here';
GRANT ALL PRIVILEGES ON faucet . * TO 'qrl'@'localhost';
USE faucet;
CREATE TABLE PAYOUT  (TX_ID VARCHAR(200), QRL_ADDR VARCHAR(100), IP_ADDR VARCHAR(255), PAYOUT DECIMAL(11,8), DATETIME DATETIME);
FLUSH PRIVILEGES;
exit
```

> **Important**: Record the user and passphrase for the database. Please use a new passphrase that is difficult to guess.

#### Configure the Site

The main files are located in the `web` directory. Copy this into the web root of your web server. First clean out any junk that may be in there.

Also move the script folder to a location where the scripts can be found. Make sure this is outside of the web root for security. Don't let apache access this folder! 

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

```php
$payoutInterval = 24; #payout interval in hours before user is valid again
$SQLservername = "localhost"; # database host
$SQLuser = "qrl"; # Database user
$SQLpassword = "YOUR_PASSWORD_HERE"; # Database password
$database = "faucet"; # Database Name
$coinhiveSecret = "YOUR_COINHIVE_SECRET_HERE"; #Coinhive Secret key
$hashes = 256; # Amount of hashes before valid, must match index.php
```

**/var/www/script/payout.py**

```python
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

```php
data-hashes ="256" # Number of hashes before validation, Must match php/main.php
data-key ="YOUR_COINHIVE_PUBLIC_KEY_HERE" #Coinhive Public Key
```

### \#4 - Automate 

Setup the faucet server to automatically send transactions when the time matches the settings in the `payout.py` script. This will run the script at that interval and search the database for addresses that match the time, and payout. If these numbers don't match you will payout more than you intend.

**Cron Job**

Edit the cron file with:

```bash
crontab -e
```

at the bottom of this file add
`0 * * * * /home/$USER/QRL-Faucet/script/payout.py`

Change the name and location to suit. The user who's crontab you are editing must have execute rights on that file. To change the schedule of payouts checkout [crontab.guru](https://crontab.guru) for more help with cron.

### \#5 - Finish Up

Fund the address printed in the site by clicking on the QRL symbol on bottom.

Test your faucet out by entering your address and see if you are paid in about an hour. If so you are ready to give away QRL!
