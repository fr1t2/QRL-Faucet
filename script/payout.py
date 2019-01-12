#!/usr/bin/python3.5
#
# payout.py is run by a cron job at an interval that is set by the ADMIN
# This script checks the database for any transactions in the last $payoutTime and if found pays them out the $amountToPay
#
# You must setup the settings here to reflect how you want the faucet to work. 
#
#

import requests
import json
import mysql.connector
import datetime
import logging

# LOGGING - This establishes the logging function and where to write the log file
logging.basicConfig(format='%(asctime)s %(message)s', filename='/home/ubuntu/.qrl/faucet.log', level=logging.INFO) 
# Mark the file the script has been initiated.
logging.info('\n\n#####################################################\nPayout.py Script Initiated\n#####################################################')
# DATABESE Settings
host = "localhost"					# Where is the database located 
user = "qrl"						# database user setup during install
passwd = "DATABASE_PASSWORD"	# database password (Must have read/write access)
database = "faucet"					# Database name setup during install
# QRL Payout settings
payoutTime = 1 						# Time from NOW() that payout is valid. This should allign with the cronjob to not miss any transactions or double pay.
payNumber = 100 					# How many transactions to combine in a single TX. The network allows a MAX of 100 addresses in a TX. (Size limitations)
payees = []							# Empty array for future payees. (Who to pay, based on submitted addresses to the faucet)
payoutList = []						# Empty array for future payouts
fee = 10 							# in shor X/10^9=shor | 1000000000 = 1QRL
amountToPay = 100 					# in shor X/10^9 | 1000000000 = 1QRL
# Grab the time
current_time = datetime.datetime.now()

# SQL Syntax | Dont change these please
sql = "SELECT QRL_ADDR from PAYOUT where DATETIME > DATE_SUB(NOW(), INTERVAL %d HOUR)" % payoutTime
Countsql = "SELECT COUNT(QRL_ADDR) from PAYOUT where DATETIME > DATE_SUB(NOW(), INTERVAL %d HOUR)" % payoutTime

# Get the list of addresses from the network and parse to a useable format (JSON)
def listAddresses():
  QRLrequest = requests.get("http://127.0.0.1:5359/api/ListAddresses")
  response = QRLrequest.text
  logging.info('listAddress Called.\nLocal wallet Address is: %s', response)
  listAddressesResp = json.loads(response)
  jsonResponse = listAddressesResp
  return(jsonResponse)


# Transfer traction onto the network
def relayTransferTxnBySlave(addresses_to, amounts, fee, master_address):
  payload = {'addresses_to': addresses_to, 'amounts': amounts, 'fee': fee, 'master_address': master_address }
  logging.info('relayTransferTxnBySlave Called. \nPayload is: %s', payload)
  QRLrequest = requests.post("http://127.0.0.1:5359/api/RelayTransferTxnBySlave", json=payload)
  response = QRLrequest.text
  relayTransferTxnBySlaveResp = json.loads(response)
  jsonResponse = relayTransferTxnBySlaveResp
  logging.info('TX HASH: %s', json.dumps(jsonResponse['tx']['transaction_hash']))
  return(jsonResponse)

# Relay a message onto the network. | Change the message
def relayMessageTxnBySlave(message, fee, master_address):
  payload = {'message': message, 'fee': fee, 'master_address': master_address }
  logging.info('relayMessageTxnBySlave Called. \nPayload is: %s', payload)
  QRLrequest = requests.post("http://127.0.0.1:5359/api/RelayMessageTxnBySlave", json=payload)
  response = QRLrequest.text
  relayMessageTxnBySlaveResp = json.loads(response)
  jsonResponse = relayMessageTxnBySlaveResp
  logging.info('TX HASH: %s \nMessage:  %s', json.dumps(jsonResponse['tx']['transaction_hash']), json.dumps(jsonResponse['tx']['message']))
  return(jsonResponse)

# Message is sent on each payout TX to the network.
def message():
	time = str(current_time)
	message = 'Another Payout from the Faucet at https://qrl.tips '
	message = '"'+message+'"'
	return(message)

# The payout address we have on the server
masterAddress = listAddresses()['addresses'][0]

# Database setup | Dont Change
mydb = mysql.connector.connect(
	host = host,
	user = user,
	passwd = passwd,
	database = database
)
# SQL stuff
mycursor = mydb.cursor()
cursor = mydb.cursor()
cursor.execute(Countsql)
DBcount = cursor.fetchone()
number_of_rows=DBcount[0]


# Check the database and if we have new addresses process them
if number_of_rows != 0:
	logging.info('There is something in the Database, processing row count........ \nWe have Found: \t %s Rows', str(number_of_rows))
	if number_of_rows > payNumber:
		print("More than "+ str(payNumber) +" addresses found")
		logging.info('We have more Addresses than a TX can fit! Our limit is: %s Addresses and we have %s. Brake it up!', payNumber, number_of_rows)
		mycursor.execute(sql)
		while True:
			batch = mycursor.fetchmany(payNumber)
			result = []
			for x in batch:
				result.append(x)
			payees = [val for QRL_ADDR in result for val in QRL_ADDR]
			logging.info('Payees List:\n %s', payees)
			payoutList = []
			for f in payees:
				payoutList.append(amountToPay)
			amount = payoutList
			if not payees:
				logging.info('Payees are empty')
			else:
				logging.info('Send one of many TX!')
				tx = relayTransferTxnBySlave(payees, amount, fee, masterAddress)
			if not batch:
				logging.info('All done send a MESSAGE!')
				messageTX = relayMessageTxnBySlave(message(), fee, masterAddress)
				logging.info('All TX Paid!')
				break
	elif number_of_rows <= payNumber:
		logging.info('We have enough Addresses for a single TX! You can have %s addresses and we have found %s', payNumber, number_of_rows)
		mycursor.execute(sql)
		myresult = mycursor.fetchall()
		result = []
		for x in myresult:
			result.append(x)
		payees  = [val for QRL_ADDR in result for val in QRL_ADDR]
		payoutList = []
		for f in payees:
			payoutList.append(amountToPay)
		amount = payoutList
		logging.info('Send a TX!')
		tx = relayTransferTxnBySlave(payees, amount, fee, masterAddress)
		logging.info('Send a MESSAGE!')
		messageTX = relayMessageTxnBySlave(message(), fee, masterAddress)
else:
	logging.info('No Addresses Found, sleep...')
	print("No Addresses to pay: " + str(number_of_rows))
	exit()