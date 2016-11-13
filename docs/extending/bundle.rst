Bundle
======

All begins with a bundle. The application uses Symfony Bundles
to build the environment. The bundles will be discovered by
puli. And loaded by composer. So you can guess which parts
are mandatory to hook into nanbando.

You can take a look into a already existing nanbando-bundle
like `Mysql Plugin`_.

Composer
--------

Create a ``composer.json`` file and register the repository on
`packagist`_.

Puli
----

Puli uses a simple configuration file in json form so create
a basic ``puli.json`` file with following content.

.. code:: json

    {
        "version": "1.0",
        "name": "<name>",
        "bindings": {
            "<uuid>": {
                "_class": "Puli\\Discovery\\Binding\\ClassBinding",
                "class": "<bundle-class>",
                "type": "nanbando/bundle"
            }
        }
    }

Bundle Class
------------

A `Symfony Bundle`_ is simply a structured set of files within a directory
that implement a single feature.

.. code:: php

    <?php

    namespace Acme\TestBundle;

    use Symfony\Component\HttpKernel\Bundle\Bundle;

    class AcmeTestBundle extends Bundle
    {
    }

In nanbando the bundle can contain a plugin (for backup tasks) or any other
extension like event-listener or commands.

.. _`packagist`: https://packagist.org/
.. _`Symfony Bundle`: http://symfony.com/doc/current/bundles.html
.. _`Mysql Plugin`: https://github.com/nanbando/mysql
