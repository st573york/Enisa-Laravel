import numpy as np
import pandas as pd
import json


def DataLoader(file_name, mode):
    """ Loads the raw dataset of variables per country.

    :param str file_name : The path to the file we want to load
    :param str mode: The format of the file we want to load

    :return: A dataframe if mode is "dataframe" or a dictionary if mode is "json"
    """

    if mode == "dataframe":
        return pd.read_csv(file_name).set_index("CountryID").dropna(how="all")
    elif mode == "json":
        with open(file_name) as user_file:
            file_contents = user_file.read()

        return json.loads(file_contents)


# def _ind_to_var(cofig):
#     """Converts the config file from jason to """
#     return JSONConfig.from_json(cofig)


def ind2_calc(row):
    """ Calculates the value of indicator IND_2, which is a weighted arithmetic average. """
    variables = np.array(row)
    weights_np = np.array([0.4, 0.4, 0.2])

    return sum(np.multiply(weights_np, variables))


def ind7_calc(row):
    """ Calculates the value of indicator IND_7, which is a normalisation by the number of enterprises. """
    return row[0] / row[1]


# def ind12_calc(data):
#     """ Calculates the value of indicator IND_12, which is a ranking (high to low) of the normalised value (by
#     population) divided by the nuber of countries. """
#     temp = data[data.columns[0]] / data[data.columns[1]]
#     ranking = temp.rank(method="min", ascending=False)
#
#     return ranking / data.shape[0]
#
#
# def ind13_calc(data):
#     """ Calculates the value of indicator IND_13, which is a ranking (high to low) of the normalised value (by
#     population) divided by the nuber of countries. """
#     temp = data[data.columns[0]] / data[data.columns[1]]
#     ranking = temp.rank(method="min", ascending=False)
#
#     return ranking / data.shape[0]
#
#
# def ind14_calc(data):
#     """ Calculates the value of indicator IND_14, which is a ranking (high to low) of the normalised value (by
#     population) divided by the nuber of countries. """
#     temp = data[data.columns[0]] / data[data.columns[1]]
#     ranking = temp.rank(method="min", ascending=False)
#
#     return ranking / data.shape[0]


def rank_high2low(data):
    """ Calculates the value of indicator IND_14, which is a ranking (high to low) of the normalised value (by
    population) divided by the nuber of countries. """
    temp = data[data.columns[0]] / data[data.columns[1]]
    ranking = temp.rank(method="min", ascending=False)

    return ranking / data.shape[0]


def ind35_calc(row):
    if row[0] == "No":
        return 0
    elif row[0] == "Yes":
        if row[1] == "Not willing to share this information":
            return 0.3
        elif row[1] == "0-20%":
            return 0.6
        elif row[1] == "21-40%":
            return 0.7
        elif row[1] == "41-60%":
            return 0.8
        elif row[1] == "61-80%":
            return 0.9
        elif row[1] == "81-100%":
            return 1
        else:
            pass
    else:
        pass


def ind49_calc(row):
    if type(row[0]) is str:
        lst = row[0].split(";")

        if len(lst) >= 17:
            return 17
        elif len(lst) == 1 and lst[0] == "All objectives above are covered in the current NCSS":
            return 17
        else:
            return len(lst)
    else:
        pass


def ind49_calc_val(dct):
    """
    The function calculates the value of indicator 49: Coverage and Objectives of the National Cybersecurity Strategy
    (NCSS). It is a multiple choice question.
    :param dct: The list of values.
    :return: The value of the indicator for a single country
    """

    lst = dct['0']['value']

    if len(lst) >= 17:
        return float(17)
    # the value 18 corresponds to answer "All objectives above are covered in the current NCSS"
    elif len(lst) == 1 and lst[0] == '18':
        return float(17)
    else:
        return float(len(lst))


def _id_to_jid():
    dictionary = {
        "IND_49": "1"
    }
    return dictionary


def functions_dict():
    f_dict = {
        "IND_2": ind2_calc,
        "IND_7": ind7_calc,
        "IND_12": rank_high2low,
        "IND_13": rank_high2low,
        "IND_14": rank_high2low,
        "IND_35": ind35_calc,
        # "IND_49": ind49_calc
        "IND_49": ind49_calc_val
    }

    return f_dict


def _questions():
    survey = DataLoader("./data/questionnaire.json", mode="json")

    dictionary = dict()

    for i in range(len(survey["contents"])):
        # qid_2_indid["qid"] = survey["contents"][i]["id"]
        dictionary[str(survey["contents"][i]["id"])] = {"identifier": survey["contents"][i]["identifier"],
                                                        "number": survey["contents"][i]["number"],
                                                        "title": survey["contents"][i]["title"],
                                                        "order": [int(i) for i in
                                                                  range(len(survey["contents"][i]["form"]))]
                                                        }

    return dictionary


def _answers():
    answers = DataLoader("./data/answers-test.json", mode="json")

    dictionary = dict()

    for i in range(len(answers["indicators"])):
        # qid_2_indid["qid"] = survey["contents"][i]["id"]
        if answers["indicators"][i]["id"] in dictionary.keys():

            dictionary[answers["indicators"][i]["id"]]['order'][answers["indicators"][i]["answers"][0]['order']] = {
                "value": answers["indicators"][i]["answers"][0]['values']}
        else:

            dictionary[answers["indicators"][i]["id"]] = {"order":
                                                              {answers["indicators"][i]["answers"][0]['order']:
                                                                   {"value": answers["indicators"][i]["answers"][0][
                                                                       'values']}
                                                               }
                                                          }

    return dictionary


def main(ind_name, mode=None):
    """ Performs the calculation of an indicator.


    :param ind_name: The indicator id. It must be the same as in the config.py file
    :param mode: If the indicator is calculated per row, or if it needs all values for all countries
    :return: A series object for the indicator's values per country
    """
    # data = DataLoader("./data/DataRaw.csv", mode="dataframe")

    # questions = _questions()

    d_answers = _answers()

    number_to_id = _id_to_jid()

    functions = functions_dict()

    if mode == "json":

        return functions[ind_name](d_answers[number_to_id[ind_name]]['order'])

    else:
        return "Under construction..!"

    # if mode == "individual":
    #
    #     return data[Ind_to_Var[ind_name]].apply(functions[ind_name], axis=1)
    #
    # elif mode == "global":
    #
    #     return functions[ind_name](data[Ind_to_Var[ind_name]])
    #
    # else:
    #     return "Invalid mode."
