Connectivity
============

Nanbando has two options to communicate with other servers.

1. Remotes: Storage to push and fetch backups.
2. Servers: Uses SSH connection to execute commands on a remote server.

Remotes
-------

The remote-storage enhances the user to push backups to or fetch backups from a secure place. Nanbando uses for that the
library `flysystem`_ which implements a really easy to use abstraction of different remote-storages.

Nanbando brings a lot of build in adapters:

* Amazon S3
* Dropbox
* Azure
* (S)FTP

This adapters can be configured in the :doc:`global configuration <configuration>`.

You can then simply push your backups with ``php nanbando.phar push`` and fetch with ``php nanbando.phar fetch``.

Servers
-------

Servers enables nanbando to connect over ssh with other servers to execute different commands there or download backups
directly to your local machine.

You can append the options ``--server`` to the following commands to execute the specified command there.

.. code::

    php nanbando.phar plugins:install --server <server-name>
    php nanbando.phar backup --server <server-name>
    php nanbando.phar information <backup-name> --server <server-name>
    php nanbando.phar get <server-name> <backup-name>

The last command will download the specified backup directly in your local directory to restore it locally. Servers can
be configured in your :doc:`global configuration <configuration>` or :doc:`local project configuration <configuration>`.
It depends if you want to share the configuration or keep it secret.

.. _`flysystem`: https://flysystem.thephpleague.com/

