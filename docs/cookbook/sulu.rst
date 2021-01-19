How to backup a Sulu application?
=================================

.. note::

    To simplify the backup configuration for sulu-applications you can use the `Sulu Plugin`_.

You can use the following configuration to backup the application using jackrabbit as phpcr backend.

.. code::

    {
        "name": "test/application",
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
                    "databaseUrl": "%env(DATABASE_URL)%"
                }
            },
            "cmf": {
                "plugin": "jackrabbit",
                "parameter": {
                    "jackrabbit_uri": "%env(PHPCR_BACKEND_URL)%",
                    "workspace": "default",
                    "path": "/cmf"
                }
            },
            "versions": {
                "plugin": "jackrabbit",
                "parameter": {
                    "jackrabbit_uri": "%env(PHPCR_BACKEND_URL)%",
                    "workspace": "default",
                    "path": "/jcr:versions"
                }
            },
            "cmf_live": {
                "plugin": "jackrabbit",
                "parameter": {
                    "jackrabbit_uri": "%env(PHPCR_BACKEND_URL)%",
                    "workspace": "default_live",
                    "path": "/cmf"
                }
            }
        },
        "require": {
            "nanbando/mysql": "^0.4.2",
            "nanbando/jackrabbit": "^0.2.1"
        }
    }

.. note::

    This configuration is optimized for Sulu (minimal) version `^2.0` with the drafting feature. If you want to backup
    earlier versions of sulu you have to adapt the parameter of the plugins.

If you use mysql as data storage for phpcr you can remove the ``cmf``, ``cmf_live`` and ``versions`` part
of the backup.

.. code::

    {
        "name": "test/application",
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
                    "databaseUrl": "%env(DATABASE_URL)%"
                }
            }
        },
        "require": {
            "nanbando/mysql": "^0.4.2"
        }
    }

.. _`Sulu Plugin`: https://github.com/nanbando/sulu
