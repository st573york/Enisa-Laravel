# -*- coding: utf-8 -*-
"""Config class"""

import json


class JSONConfig:
    """Config class which contains data, train and model hyperparameters"""

    def __init__(self, data):
        self.data = data

    @classmethod
    def from_json(cls, cfg):
        """Creates config from json"""
        params = json.loads(json.dumps(cfg), object_hook=HelperObject)
        return cls(params.data)


class HelperObject(object):
    """Helper class to convert json into Python object"""

    def __init__(self, dict_):
        self.__dict__.update(dict_)