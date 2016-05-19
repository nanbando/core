Installation
------------

To install the application simply download the executable and move it to the global bin folder.

.. code:: bash

    wget http://nanbando.github.io/core/nanbando.phar
    wget http://nanbando.github.io/core/nanbando.phar.pubkey
    chmod +x nanbando.phar
    mv nanbando.phar /usr/local/bin/nanbando
    mv nanbando.phar.pubkey /usr/local/bin/nanbando.pubkey

After first installation you can update the application with a built-in command.

.. code:: bash

    nanbando self-update

.. note::

    The executable is signed with a OpenSSL private key. This ensures the origin of the build.
