# README #

### What is this repository for? ###

This repo implements automatic data collection from Eurostat, 
The configuration of which data to collect is at eurostat_indicators_config.py. 
Once the data is collected, the script connects to the database and inserts the data into two tables, 
eurostat_indicators and eurostat_indicators_variables.

### How do I get set up? ###

To run the script, first install the requirements. You will also need a .env file with the credentials
of the database that are needed for the connection.

Once you're set up, you only need to run main.py.
