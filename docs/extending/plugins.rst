Plugins
=======

Plugins are symfony-services tagged with ``<tag name="nanbando.plugin" alias="{alias}"/>`` inside a :doc:`bundle`. The
alias can be used in the Local-:doc:`../configuration`.

.. code:: xml

     <service id="plugins.mysql" class="Nanbando\Plugin\Mysql\MysqlPlugin">
        <argument type="service" id="output"/>
        <argument type="service" id="temporary_files"/>

        <tag name="nanbando.plugin" alias="mysql"/>
    </service>
