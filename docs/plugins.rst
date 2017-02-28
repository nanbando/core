Plugins
=======

Nanabando was written with extensibility (see :doc:`extending/index`) in mind. To keep the core as small as possible
only one plugin is included in the application. But nanbando also provides optional plugins which can be installed by
each backup-project.

Usage
-----

You can use a plugin by adding it to the ``nanbando.json`` file. There you can configure it like other `composer`_
projects in the require section of the file.

.. code:: json

    {
        "name": "application",
        "backup": {
            "su_standard": {
                "plugin": "mysql",
                "parameter": {
                    "username": "root",
                    "database": "your_database"
                }
            }
        },
        "require": {
            "nanbando/mysql": "^0.1"
        }
    }

To install this plugin run the ``reconfigure`` command. It will install the plugin with the `embedded-composer`_ and
reconfigure the local application. After that you can run the ``backup`` command to backup the database and ``restore``
to restore the database.

Available Plugins
-----------------

This list of plugins is currently quite small but there should be more plugins soonish.

- ``nanbando/mysql`` - This plugin backups your mysql-database with the ``mysqldump`` command
- ``nabando/jackrabbit`` - This plugin will backups your jackrabbit data by exporting into xml
- ``nabando/sulu`` - This plugin provides presets and auto-detection for sulu applications

.. _`composer`: https://getcomposer.org/
.. _`embedded-composer`: https://github.com/dflydev/dflydev-embedded-composer
