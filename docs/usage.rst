Usage
=====

Before we can start to create backup-projects we have to configure the local and remote storage. This global
configuration will be shared for all projects of the executing user. The configuration will be written in the file
``~/.nanbando.yml``.

.. code:: yaml

    nanbando:
        storage:
            local_directory: "%home%/nanbando/local"
            remote_service: filesystem.remote

    oneup_flysystem:
        adapters:
            remote:
                local:
                    directory: "%home%/nanbando/remote"

        filesystems:
            remote:
                adapter: remote
                alias: filesystem.remote
                plugins:
                    - filesystem.list_files

.. note::

    In the configuration you can use the parameter ``%home%`` which points to the home directory of the current user.

The application contains a simple directory backup plugin which we will use in this simple usage example. To start a new
backup goto the root directory of your website and create a file named ``nanbando.json`` which contains the
configuration and later also the dependencies for this backup-project.

.. code:: json

    {
        "name": "application",
        "backup": {
            "data": {
                "plugin": "directory",
                "parameter": {
                    "directory": "path/to/data/directory"
                }
            }
        },
        "require": {
        }
    }

After you have created this file you can run following command to configure the local installation with the given
configuration. If you have added requirements to the configuration the application will install them into the folder
``.nanbando``.

.. note::

    For readonly filesystems you can overwrite the folder ``.nanando`` by setting the environment variable
    ``NANBANDO_DIR``.

.. code:: bash

    php nanbando.phar reconfigure
    php nanbando.phar backup

The second command will create a new backup zip in the local folder ``~/nanbando/local/application/<date>.zip``.

After this steps you can do following steps:

- ``php nanbando.phar restore`` - restore a local backup
- ``php nanbando.phar push`` - push backups to remote storage
- ``php nanbando.phar fetch`` - fetch a backup on a different machine to restore it there
