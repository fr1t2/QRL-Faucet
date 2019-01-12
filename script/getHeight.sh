#!/bin/bash
# This script grabs the curent chain height of the local node. Good to see that everything is up to date and syncing correctly
curl --silent -XGET http://127.0.0.1:5359/api/GetHeight |jq '.height' |cut -d '"' -f 2
