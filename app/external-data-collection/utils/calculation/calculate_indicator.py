
def indicator_avg(data, cols):
    """
    :param data: pandas dataframe, this dataframe must have all different variables into columns
    :param cols: the variables of the indicator
    :return: new column as the average of cols values
    """
    return data[cols].mean(axis=1)


def indicator_inverse(data, col, max_value):
    """

    :param data: pandas dataframe, this dataframe must have the one variable into column
    :param col: the name of the variable
    :param max_value: the maximum value to use to invert the indicator (for percentages we use 100)
    :return: new column as the inverted value of the variable
    """
    return max_value - data[col]
