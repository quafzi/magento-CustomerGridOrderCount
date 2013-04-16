This extension adds a column "order count" to your Magento customer grid.

There are two versions of this extension:

Branch "master"

* add column using event/observer pattern, see http://www.webguys.de/magento/turchen-23-pimp-my-produktgrid/
* highly compatible solution
* no csv export for this column

Branch "csv"

* add column using plain old rewrite
* less compatible version, you could have some conflicts with other extensions
* supports csv export for this column
