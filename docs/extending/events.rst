Events
======

Nanbando issues events which can be listened to by using the standard
symfony event dispatcher. You can register a listener in your
dependency injection configuration as follows:

.. code-block:: xml

     <service id="nanbando_mysql.event_listener.backup" class="Nanbando\Plugin\Mysql\EventListener\BackupListener">
         <tag name="kernel.event_listener" event="<event_name>" method="methodToCall" />
     </service>

Backup
------

The backup fires the event ``nanbando.pre_backup`` before the
process starts and ``nanbando.post_backup`` after the backup
is finished.

The main event is ``nanbando.backup`` which does the magic and
backup the data.

Restore
-------

The backup fires the event ``nanbando.pre_restore`` before the
process starts and ``nanbando.post_restore`` after the backup
is finished.

The main event is ``nanbando.restore`` which does the magic and
restores the data.
