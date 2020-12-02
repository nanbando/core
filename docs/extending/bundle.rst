Bundle
======

All begins with a bundle. The application uses Symfony Bundles
to build the environment. The bundles will be discovered by
crawling the composer files of the installed packages.
And loaded by composer. So you can guess which parts are
mandatory to hook into nanbando.

You can take a look into a already existing nanbando-bundle
like `Mysql Plugin`_.

Composer
--------

Create a ``composer.json`` file and register the repository on
`packagist`_.

Add a ``extra`` configuration inside of the ``composer.json``.

.. code:: json

    "extra": {
        "nanbando-bundle-class": "<bundle-class>"
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
