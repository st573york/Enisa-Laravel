import pandas as pd
import os
from dotenv import load_dotenv
import logging
import datetime
from sqlalchemy import create_engine
from sqlalchemy.sql import text


def connect_to_dbase():
    """Function to connect to database."""
    # take the current directory (utils)
    current_dir = os.path.dirname(os.path.realpath(__file__))
    # the .env file is in directory 3 levels back the current directory
    target_dir = os.path.sep.join(current_dir.split(os.path.sep)[:-3])

    load_dotenv(dotenv_path=os.path.join(target_dir, '.env'))

    # when the .env file is in the working dir
    # load_dotenv()
    logging.info("Connecting to database...")
    try:
        engine = create_engine('mysql+mysqldb://%s:%s@%s:%i/%s'
                               % (
                                   os.getenv("DB_USERNAME"), os.getenv("DB_PASSWORD"), os.getenv("DB_HOST"),
                                   int(os.getenv("DB_PORT")),
                                   os.getenv("DB_DATABASE")))

        logging.info("Connected to database!")

        return engine

    except Exception as e:
        logging.error(f"Not connected to database due to {e}")


def load_table(engine, table_name):
    """Function that loads a table from database"""
    sql = f"SELECT * FROM {table_name};"

    df = pd.read_sql_query(sql, engine)

    return df


def _get_last_id(engine, table_name):
    """Function to get the last id of a table in database"""
    statement = text(f"""SELECT max(id) from {table_name};""")
    with engine.connect() as con:
        rs = con.execute(statement)

        for row in rs:
            max_id = row

    return max_id


def _get_table_ids(engine, table_name, cols, identifier_col_name, identifier):
    """Function to get the identifiers of a table in database"""
    statement = text(
        f"""SELECT distinct {",".join([c for c in cols])} from {table_name} where {identifier_col_name} = "{identifier}";""")
    ids = []
    country_ids = []
    with engine.connect() as con:
        rs = con.execute(statement)

        for id_ind, country in rs:
            ids.append(id_ind)
            country_ids.append(country)

    return ids, country_ids


def _build_next_ids(max_id, data):
    """Function to create the next group of ids to be inserted"""
    # if table is empty create the ids from 1 until the length of the dataframe, else create the ids from the maximum id
    # and on
    if not max_id[0]:
        id_lst = list(range(1, len(data) + 1))
    else:
        id_lst = list(range(max_id[0] + 1, len(data) + max_id[0] + 1))

    return id_lst


def insert_indicators(engine, table_name, data, CFG, indicator, country_to_id):
    """Function to insert indicators' values into eurostat_indicators"""
    # create a dataframe with the exact same form as the table
    data_to_table = pd.DataFrame(
        {
            # 'id': id_lst,
            'name': CFG['name'],
            'source': CFG['source'],
            'identifier': CFG['identifier'].replace('.', ''),
            'country_id': data['geo\TIME_PERIOD'].map(country_to_id),
            'report_year': CFG['startPeriod'],
            'value': data[indicator],
            'created_at': datetime.datetime.now(),
            'updated_at': datetime.datetime.now()

        }
    )

    # insert data to table
    try:
        data_to_table.to_sql(table_name, engine, os.getenv("DB_DATABASE"), if_exists='append', index=False)
        logging.info(f"The data were imported successfully into {table_name}")
    except Exception as e:
        logging.error(f"The data were not imported due to {e}..")

    return data_to_table


def insert_variables(engine, table_name, data, CFG, country_to_id, indicators_table, var_names):
    """Function to insert variables' values into eurostat_indicator_variables. In order to insert the values we need
    to take indicators' id."""
    max_id = _get_last_id(engine, table_name)

    id_lst = _build_next_ids(max_id, data)

    id_ind, country_id = _get_table_ids(engine, table_name='eurostat_indicators', cols=['id', 'country_id'],
                                        identifier_col_name='identifier',
                                        identifier=indicators_table['identifier'].unique()[0])

    variable_identifier = dict(zip(CFG['var_codes'], [CFG['identifier'].replace('.', '') + '_' +
                                                      str(i) for i in list(range(1, len(CFG['var_codes']) + 1))]))

    country_id_to_ind_id = pd.Series(id_ind, index=country_id).to_dict()

    # create a dataframe with the exact same form as the table
    data_to_table = pd.DataFrame(
        {
            'id': id_lst,
            'eurostat_indicator_id': None,
            'country_id': data['geo\TIME_PERIOD'].map(country_to_id),
            'variable_identifier': data['indic_is'].map(variable_identifier),
            'variable_code': data['indic_is'],
            'variable_value': data[data.columns[-1]],
            'created_at': datetime.datetime.now(),
            'updated_at': datetime.datetime.now(),
            'variable_name': None

        }
    )

    data_to_table['eurostat_indicator_id'] = data_to_table['country_id'].map(country_id_to_ind_id)
    data_to_table['variable_name'] = data_to_table['variable_code'].map(var_names)

    # insert data to table
    try:
        data_to_table.to_sql(table_name, engine, os.getenv("DB_DATABASE"), if_exists='append', index=False)
        logging.info(f"The data were imported successfully into {table_name}")
    except Exception as e:
        logging.error(f"The data were not imported due to {e}..")
