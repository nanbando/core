Configuration
=============

The configuration is devided into two parts - global (optional) and project configuration.

.. warning::

    After changing configuration please run command ``reconfigure`` to be sure that the configuration will be used for
    recreating the symfony container.

Global Configuration
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

    The configuration  documentation for the ``oneup_flysystem`` can be found on github `OneupFlysystemBundle`_.

For nanbando you have to define the local directory, where the backup command can place the backup archives, and the
remote filesystem-service which can be configured in the ``oneup_flysystem`` extension.

By default the ``local_directory`` will be set to ``%home%/nanbando`` and the ``remote_service`` will be ``null``. This
leads to local backups will work out of the box but all commands (``fetch``, ``push``) which needs the remote-storage
will be disabled.

Local Configuration
-------------------

The local configuration contains the name, backup configuration and the additional :doc:`plugins/index`.

.. code:: json

    {
        "name": "application",
        "parameters": {
            "directory": "path/to/data/directory"
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

.. _`OneupFlysystemBundle`: https://github.com/1up-lab/OneupFlysystemBundle/blob/master/Resources/doc/index.md#step3-configure-your-filesystems
