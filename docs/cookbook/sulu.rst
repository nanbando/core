How to backup a Sulu application?
=================================

.. note::

    To simplify the backup configuration for sulu-applications you can use the `Sulu Plugin`_.

You can use the following configuration to backup the application using jackrabbit as phpcr backend.

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
                    "directory": "var/uploads"
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
                    "workspace": "%phpcr_workspace%",
                    "path": "/cmf"
                }
            },
            "versions": {
                "plugin": "jackrabbit",
                "parameter": {
                    "jackrabbit_uri": "%jackrabbit_uri%",
                    "workspace": "%phpcr_workspace%",
                    "path": "/jcr:versions"
                }
            },
            "cmf_live": {
                "plugin": "jackrabbit",
                "parameter": {
                    "jackrabbit_uri": "%jackrabbit_uri%",
                    "workspace": "%phpcr_workspace%_live",
                    "path": "/cmf"
                }
            }
        },
        "require": {
            "nanbando/mysql": "^0.1",
            "nanbando/jackrabbit": "^0.1"
        }
    }

.. note::

    This configuration is optimized for Sulu (minimal) version `^1.3` with the drafting feature. If you want to
    backup earlier versions you can omit the backup section `cmf_Live`. For the standard edition you have to
    adapt the path to the uploads directory.

If you use mysql as data storage for phpcr you can remove the ``cmf``, ``cmf_live`` and ``versions`` part
of the backup.

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
            "nanbando/mysql": "^0.1"
        }
    }

.. _`Sulu Plugin`: https://github.com/nanbando/sulu
