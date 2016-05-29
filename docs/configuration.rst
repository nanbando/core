Configuration
=============

The configuration is devided into two parts - global and project configuration.

Global Configuration
--------------------

The global congfiguration is placed in the user home directory. This will be used for all projects used by the user.
Put this configuration into ``~/.nanbando.yml``.

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

    The configuration  documentation for the ``oneup_flysystem`` can be found on github `OneupFlysystemBundle`_.

For nanbando you have to define the local directory, where the backup command can place the backup archives, and the
remote filesystem-service which can be configured in the ``oneup_flysystem`` extension.

Local Configuration
-------------------

The local configuration contains the name, backup configuration and the additional :doc:`plugins/index`.

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

The ``backup`` section can contain as much parts as needed. Each plugin can provide its own ``parameter`` structure.

.. _`OneupFlysystemBundle`: https://github.com/1up-lab/OneupFlysystemBundle/blob/master/Resources/doc/index.md#step3-configure-your-filesystems