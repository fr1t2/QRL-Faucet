#!/bin/bash
# getBalance.sh grabe the walletAPI address and gets the balance from the network.
# This script shows the curent faucet balance in the main page.
# Define the ListAddresses into variable and format to simple output
address=`curl --silent -XGET http://127.0.0.1:5359/api/ListAddresses | jq '.addresses[0]' |cut -d '"' -f 2`

curl --silent -XPOST http://127.0.0.1:5359/api/GetBalance -d '{"address": "'$address'"}' |jq '.balance' | rev |sed 's/./&./10' |rev
