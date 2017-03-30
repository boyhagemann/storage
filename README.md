# Immutable Data Storage
This is a proof of concept of an immutable data storage system.
No data will ever get mutated. 
Every change for both the data schema and the data itself is versioned. 

This package can be used in any API framework.

### Testing
Run `vendor/bin/phpunit` to run all tests.


### Drivers
By default, it ships with a Mysql driver.
But you can make your own driver, as long as it follows the interfaces.
The drivers must have a test file that extends the `AbstractTest.php`.

The package is divided in two concepts:
1. Entities
2. Records

### Entities
An Entity reflects a table in MySQL.
It holds the structure of the data.
An `Entity` has many `Field`s that defines the structure.
This is what happens of something changes in an Entity or a Field:
* If an `Entity` changes, the version of the `Entity` increments with 1.
* If a `Field` changes, the version of the `Field` and its `Entity` increments with 1.

### Records
A Record reflects a table row in MySQL.
Each Records has a unique `_id` and holds the actual data.
A `Record` has many `Value`s that makes up the data.
This is what happens of something changes in a Record:
* If the changes of the Record differ from the last version, then the version of the `Record` increments with 1.
* If a provided `Value` differs from the last version of this Value, the version for this Value increments with 1.

### interface

##### Entity
* find(query, options)
* first(query, options)
* insert(data, options)
* update(id, data, options)
* upsert(data, options)
* delete(id, options)

##### Field
* find(query, options)
* first(query, options)
* insert(data, options)
* update(id, data, options)
* upsert(data, options)
* delete(id, options)

##### Record
* find(query, options)
* first(query, options)
* insert(data, options)
* update(id, data, options)
* upsert(data, options)
* delete(id, options)

##### Value
* find(query, options)
* first(query, options)
* insert(data, options)
* update(id, data, options)
* upsert(data, options)
* delete(id, options)
