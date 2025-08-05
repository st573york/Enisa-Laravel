from configs.eurostat_indicators_config import CFG
from eurostatApi import collect_data, consolidate_data, calculate_indicators, write_data
import logging
import sys
from utils.database_utils import connect_to_dbase, insert_indicators, insert_variables


def collect_indicator_data(CFG):
    """Function to collect eurostat data, compute indicator value if needed and write to database."""
    for indicator in CFG['data'].keys():
        indicator_identifier = CFG['data'][indicator]['identifier']
        countries = CFG['data'][indicator]['countries']
        code = CFG['data'][indicator]['code']
        ind_type = CFG['data'][indicator]['ind_type']
        var_codes = CFG['data'][indicator]['var_codes']
        unit = CFG['data'][indicator]['unit']
        size_emp = CFG['data'][indicator]['size_emp']
        startPeriod = CFG['data'][indicator]['startPeriod']
        endPeriod = CFG['data'][indicator]['endPeriod']
        needs_computation = CFG['data'][indicator]['computation']['needs_computation']
        computation_alg = CFG['data'][indicator]['computation']['computation_alg']
        max_value = CFG['data'][indicator]['computation']['max_value']

        data, var_names = collect_data(code=code, indic_is=var_codes, ind_type=ind_type, countries=countries, unit=unit,
                                       size_emp=size_emp, startPeriod=startPeriod, endPeriod=endPeriod,
                                       ind_id=indicator_identifier)

        if needs_computation:
            # get a new dataframe pivoted with extra column "indicator-identifier" with the indicator's values
            # also get the original one, because we need it for the variables table
            data, original_data = calculate_indicators(data, cols=var_codes, computation_alg=computation_alg,
                                                       name=indicator,
                                                       max_value=max_value)
        else:
            # an extra column  "indicator-identifier" is added, with the same value as the variable
            # we copy the data to original_data because we need it for variables table
            original_data = data.copy()
            data[indicator] = data[data.columns[-1]]

        # dbase mapping of ids and countries
        country_to_id = {
            'AT': 1, 'BE': 2, 'BG': 3, 'HR': 4, 'CY': 5, 'CZ': 6, 'DK': 7, 'EE': 8, 'FI': 9, 'FR': 10, 'DE': 11,
            'EL': 12, 'HU': 13, 'IE': 14, 'IT': 15, 'LV': 16, 'LT': 17, 'LU': 18, 'MT': 19, 'NL': 20, 'PL': 21,
            'PT': 22, 'RO': 23, 'SK': 24, 'SI': 25, 'ES': 26, 'SE': 27
        }

        engine = connect_to_dbase()

        # function to insert the indicators values into indicators_eurostat table
        indicators_table = insert_indicators(engine=engine, table_name='eurostat_indicators', data=data,
                                             CFG=CFG['data'][indicator],
                                             indicator=indicator, country_to_id=country_to_id)
        # function to insert the variables values into indicators_eurostat table
        insert_variables(engine=engine, table_name='eurostat_indicator_variables', data=original_data,
                         CFG=CFG['data'][indicator], country_to_id=country_to_id, indicators_table=indicators_table,
                         var_names=var_names)

        logging.info("Data have been collected and written into the database..!")


def consolidate_and_write(data_dir, filename):
    """Function to consolidate already collected data from Excel files and write them to one all.xlsx Excel file."""
    data = consolidate_data(data_dir)

    data.to_excel(filename)


def write_in_excel(CFG):
    for indicator in CFG['data'].keys():
        indicator_identifier = CFG['data'][indicator]['identifier']
        countries = CFG['data'][indicator]['countries']
        code = CFG['data'][indicator]['code']
        ind_type = CFG['data'][indicator]['ind_type']
        var_codes = CFG['data'][indicator]['var_codes']
        unit = CFG['data'][indicator]['unit']
        size_emp = CFG['data'][indicator]['size_emp']
        startPeriod = CFG['data'][indicator]['startPeriod']
        endPeriod = CFG['data'][indicator]['endPeriod']
        needs_computation = CFG['data'][indicator]['computation']['needs_computation']
        computation_alg = CFG['data'][indicator]['computation']['computation_alg']
        max_value = CFG['data'][indicator]['computation']['max_value']
        path = CFG['data'][indicator]['path']

        data, var_names = collect_data(code=code, indic_is=var_codes, ind_type=ind_type, countries=countries, unit=unit,
                                       size_emp=size_emp, startPeriod=startPeriod, endPeriod=endPeriod,
                                       ind_id=indicator_identifier)

        original_data = data

        if needs_computation:
            # get a new dataframe pivoted with extra column "indicator-identifier" with the indicator's values
            # also get the original one, because we need it for the variables table
            data, original_data = calculate_indicators(data, cols=var_codes, computation_alg=computation_alg,
                                                       name=indicator,
                                                       max_value=max_value)

        write_data(data, original_data, path=path, ind_id=indicator_identifier,
                   computation=computation_alg if needs_computation else None, indicator_name=indicator)

    logging.info("Data have been collected..!")


def main(mode='collect'):
    """Two modes for main function, collect - to collect data from eurostat, compute indicators if needed and write
    them to database or consolidate - consolidate already collected data from Excel files and write them to one
    all.xlsx Excel file """

    if mode == 'collect':
        collect_indicator_data(CFG)
    elif mode == 'consolidate':
        consolidate_and_write('data/eurostatIndicators/2024/', "all_data_2024.xlsx")


if __name__ == '__main__':
    logging.root.handlers = []
    logging.basicConfig(format="%(asctime)s: %(levelname)s: %(message)s", level=logging.INFO,
                        datefmt="%Y-%m-%d %H:%M:%S", handlers=[logging.FileHandler("internal_logs.log"),
                                                               logging.StreamHandler(sys.stdout)])

    main(mode="collect")

    # write_in_excel(CFG)

    logging.info("Collection completed..!")
