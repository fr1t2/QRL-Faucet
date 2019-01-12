#!/bin/bash
# This script is to grab the address for donation on themain page
curl --silent -XGET http://127.0.0.1:5359/api/ListAddresses | jq '.addresses[0]' |cut -d '"' -f 2
