# Immutable Data Storage
This is a proof of concept of an immutable data storage system.
No data will ever get mutated. 
Every change for both the data schema and the data itself is versioned. 

This package can be used in any API framework.

### Drivers
By default, it ships with a Mysql driver.
But you can make your own driver, as long as it follows the interfaces.
The drivers must pass the general tests provided in this package.


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
