
## Install
 
 ```
 composer install
 ```

## Build
 
 ```
 # beforehand disable phar.readonly
 # echo "phar.readonly = Off" >> /etc/php/7.3/cli/php.ini
 
 php box.phar build
 ```


## Examples
 
 ```
//Server to file
//From host to file using ssh
bin/redisdump.phar --from user@domain --to redis_$(date '+%Y%m%d%H%M%S').dump --from-redis-pass password

//From host to file using connection to tcp port
bin/redisdump.phar --from 127.0.0.1:6379 --to redis_$(date '+%Y%m%d%H%M%S').dump --from-redis-pass password



//File to server
//From file to host using ssh
bin/redisdump.phar --from filename.dump --to user@domain --to-redis-pass password

//From file to host using connection to tcp port
bin/redisdump.phar --from filename.dump --to 127.0.0.1:6379 --to-redis-pass password



//Server to serevr
//From host to host using ssh
bin/redisdump.phar --from user1@domain1 --to user2@domain2 --from-redis-pass password1 --to-redis-pass password2 

//From host to host using connection to tcp port
bin/redisdump.phar --from 127.0.0.1:6379 --to 192.168.1.1:6379 --from-redis-pass password1 --to-redis-pass password2


//You can specify delimiter, which is using to generate short keys in selection menu
//(":" is used by default)
bin/redisdump.phar --key-delimiter : 


//Also you can ask to flush a destination server before import
bin/redisdump.phar --flush-destination-server ... 
 ```

## How does redisdump work 
* If you want to connect using an ssh, pass him an address splitted with "@" symbol ("@" is required)
```
redisdump --from user@domain ...
```
* If you want to connect using a tcp port, pass him an address splitted with ":" symbol (":" is required)
```
redisdump --from 127.0.0.1:6379 ...
```
* In other cases, redisdump consider, that you passed him a filename, even if you wrote 127.0.0.1, localhost or any domain name
```
redisdump --from user@domain --to 127.0.0.1 # will create a file "127.0.0.1" 
```


## Bugs
* cli menu crashes if you hold arrows
