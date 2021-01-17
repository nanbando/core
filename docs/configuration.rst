Configuration
=============

The configuration is devided into two parts - global (optional) and project configuration.

Global configuration
--------------------

The global configuration is placed in the user home directory. This will be used for all projects used by the user.
Put this configuration into ``~/.nanbando.yml``.

**Local** directory (e.g. a mounted NFS drive):

.. code:: yaml

    nanbando:
        storage:
            local_directory: "%home%/nanbando"
            remote:
                local:
                    directory: "%home%/nanbando/remote"

AWS **S3** bucket:

.. code:: yaml

    nanbando:
        storage:
            local_directory: "%home%/nanbando"
            remote:
                s3:
                    client:
                        region: 'eu-central-1'
                        credentials:
                            key: '...'
                            secret: '...'
                    bucket: 'bucket-name'

Digitalocean **S3** bucket:

.. code:: yaml

    nanbando:
        storage:
            local_directory: "%home%/nanbando"
            remote:
                s3:
                    client:
                        region: 'fra1'
                        endpoint: 'https://fra1.digitaloceanspaces.com'
                        credentials:
                            key: '...'
                            secret: '...'
                    bucket: 'bucket-name'

**Google Cloud Storage** remote bucket:

.. code:: yaml

    nanbando:
        storage:
            local_directory: "%home%/nanbando"
            remote:
                s3:
                    client:
                        projectId: 'project-id'
                        keyFilePath: '/path/to/key.json'
                    bucket: 'bucket-name'

.. note::

    This global configuration can also be configured inside the ``nanbando.json`` file.

For nanbando you have to define the local directory, where the backup command can place the backup archives, and the
connection details for the remote destination.

By default the ``local_directory`` will be set to ``%home%/nanbando`` and the ``remote`` will be ``null``. This
leads to local backups will work out of the box but all commands (``fetch``, ``push``) which needs the remote-storage
will be disabled.

Local project configuration
---------------------------

The local configuration contains the name, backup configuration and the additional :doc:`plugins`.

.. code:: json

    {
        "name": "application",
        "parameters": {
            "directory": "path/to/data/directory"
        },
        "servers": {
            "production": {
                "ssh": {
                    "host": "<ip-address>",
                    "username": "%env(SSH_USERNAME)%",
                    "password": "<your-password|true>"
                },
                "directory": "test-data",
                "executable": "../Development/nanbando/bin/nanbando"
            }
        },
        "backup": {
            "data": {
                "plugin": "directory",
                "parameter": {
                    "directory": "%directory%"
                }
            }
        },
        "require": {
        }
    }

The ``backup`` section can contain as much parts as needed. Each plugin can provide its own ``parameter`` structure.

.. note::

    The section ``parameters`` can be used to define global parameters which can be used in the plugin configuration.
    To import files place them in the ``imports`` array. This can be used to reuse the symfony-application parameter. As
    an alternative to the ``parameters`` you can use ``%env(...)%`` and the ``.env`` file handling of `Symfony DotEnv`_.

Server Configuration
--------------------

You can specify the servers-configuration in the local project or global configuration. It depends if you want to share
the configuration or keep it secret.

Currently nanbando is able to connected over ssh to the remote server. As authentication method ``username & password``
or ``rsakey file`` is available.

.. code::

    nanbando:
        servers:
            production:
                ssh:
                    host: <ip-address>
                    username: nanbando
                    password: <your-password|true>
                    rsakey:
                        file: <path>
                        password: <your-password|true>
                directory: /var/www
                executable: nanbando

As an example this configuration is from the "Global configuration" - but the same as json is also available in
"Local project configuration".

The password is optional in the configuration you will be asked for it when nanbando needs it.

.. note::

    You can also use environment variables to configure different values for ssh-connections. Use this variable names:
    ``NANBANDO_SSH_USERNAME``, ``NANBANDO_SSH_PASSWORD``, ``NANBANDO_SSH_RSAKEY_FILE`` and
    ``NANBANDO_SSH_RSAKEY_PASSWORD``.

Process
-------

Each backup-part has an optional configuration parameter ``process``. The process can be passed (also multiple times) to
the backup-command ``nanbando backup -p files -p database``. All backup-parts which contains one of the passed processes
will be executed. The restore process uses the passed parameter (will be stored in the backup file)from the backup call.

.. code:: json

    {
        "backup": {
            "uploads": {
                "plugin": "directory",
                "process": ["files"],
                "parameter": {
                    "directory": "var/uploads"
                }
            },
            "indices": {
                "plugin": "directory",
                "process": ["optional"],
                "parameter": {
                    "directory": "var/indices"
                }
            },
            "database": {
                "plugin": "mysql",
                "process": ["database"],
                "parameter": {
                    "username": "%database_user%",
                    "password": "%database_password%",
                    "database": "%database_name%"
                }
            }
        }
    }

As an example you could backup the database each hour and each night also the file in the uploads folder. Therefor you
could restore user-data in a smaller granularity than the files but the resulting backups will use less disk space and
the hourly backup will run faster.

.. _`Symfony DotEnv`: https://symfony.com/doc/4.1/components/dotenv.html
