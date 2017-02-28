Configuration
=============

The configuration is devided into two parts - global (optional) and project configuration.

.. warning::

    After changing configuration please run command ``reconfigure`` to be sure that the configuration will be used for
    recreating the symfony container.

Global configuration
--------------------

The global congfiguration is placed in the user home directory. This will be used for all projects used by the user.
Put this configuration into ``~/.nanbando.yml``.

.. code:: yaml

    nanbando:
        storage:
            local_directory: "%home%/nanbando"
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

    The configuration documentation for the ``oneup_flysystem`` can be found on github `OneupFlysystemBundle`_.

For nanbando you have to define the local directory, where the backup command can place the backup archives, and the
remote filesystem-service which can be configured in the ``oneup_flysystem`` extension.

By default the ``local_directory`` will be set to ``%home%/nanbando`` and the ``remote_service`` will be ``null``. This
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
                    "username": "nanbando",
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
    To import files place them in the ``imports`` array. This can be used to reuse the symfony-application parameter.

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

.. _`OneupFlysystemBundle`: https://github.com/1up-lab/OneupFlysystemBundle/blob/master/Resources/doc/index.md#step3-configure-your-filesystems
