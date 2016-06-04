How to backup a Sulu application?
=================================

You can use the following configuration to backup the application using jackrabbit as phpcr data storage.

.. code::

    {
        "name": "test-application",
        "imports": [
            "app/config/parameters.yml"
        ],
        "parameters": {
            "jackrabbit_uri": "http://localhost:8080/server/"
        },
        "backup": {
            "uploads": {
                "plugin": "directory",
                "parameter": {
                    "directory": "uploads"
                }
            },
            "database": {
                "plugin": "mysql",
                "parameter": {
                    "username": "%database_user%",
                    "password": "%database_password%",
                    "database": "%database_name%"
                }
            },
            "cmf": {
                "plugin": "jackrabbit",
                "parameter": {
                    "jackrabbit_uri": "%jackrabbit_uri%",
                    "path": "/cmf"
                }
            },
            "versions": {
                "plugin": "jackrabbit",
                "parameter": {
                    "jackrabbit_uri": "%jackrabbit_uri%",
                    "path": "/jcr:versions"
                }
            }
        },
        "require": {
            "nanbando/mysql": "dev-master",
            "nanbando/jackrabbit": "dev-master"
        }
    }

If you use mysql as data storage for phpcr you can remove the ``cmf`` and ``versions`` part of the backup.

.. code::

    {
        "name": "test-application",
        "imports": [
            "app/config/parameters.yml"
        ],
        "backup": {
            "uploads": {
                "plugin": "directory",
                "parameter": {
                    "directory": "uploads"
                }
            },
            "database": {
                "plugin": "mysql",
                "parameter": {
                    "username": "%database_user%",
                    "password": "%database_password%",
                    "database": "%database_name%"
                }
            }
        },
        "require": {
            "nanbando/mysql": "dev-master"
        }
    }
